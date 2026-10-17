import fs from "node:fs";
import path from "node:path";
import crypto from "node:crypto";
import { execSync, execFileSync } from "node:child_process";
const root = path.resolve(import.meta.dirname, "..");
const version = process.argv[2] || "1.0.0-rc.1";
if (!/^[0-9A-Za-z.-]+$/.test(version)) throw new Error("Invalid version");
const releases = path.join(root, "release"),
  target = path.join(releases, version);
fs.mkdirSync(releases, { recursive: true });
if (fs.existsSync(target))
  throw new Error(
    "Release already exists; choose a new version to preserve it.",
  );
execSync("npm run build", { cwd: root, stdio: "inherit" });
fs.mkdirSync(target);
const server = path.join(target, "server");
const excluded = new Set([
  "vendor",
  "node_modules",
  "tests",
  "scripts",
  ".git",
  "storage",
  "package.json",
  "package-lock.json",
  "vite.config.js",
  "README.md",
  ".editorconfig",
  ".gitattributes",
  ".phpunit.result.cache",
  ".phpunit.cache",
  "phpunit.xml",
  "auth.json",
]);
fs.cpSync(path.join(root, "backend"), server, {
  recursive: true,
  filter: (p) => {
    const rel = path.relative(path.join(root, "backend"), p),
      parts = rel.split(path.sep);
    if (parts.some((p) => excluded.has(p))) return false;
    const name = path.basename(p);
    return (
      (!name.startsWith(".env") || name === ".env.example") &&
      !/\.sqlite(?:-.*)?$/.test(name) &&
      !(parts[0] === 'public' && parts[1] === 'hot') &&
      !(parts[0] === "bootstrap" && parts[1] === "cache")
    );
  },
});
for (const dir of [
  "app/private",
  "app/public",
  "framework/cache/data",
  "framework/sessions",
  "framework/views",
  "logs",
])
  fs.mkdirSync(path.join(server, "storage", dir), { recursive: true });
fs.mkdirSync(path.join(server, "bootstrap/cache"), { recursive: true });
execSync(
  "composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader",
  { cwd: server, stdio: "inherit" },
);
fs.cpSync(path.join(root, "docs/operations"), path.join(target, "operations"), {
  recursive: true,
});
fs.copyFileSync(
  path.join(root, "deployment/production.env.example"),
  path.join(target, "production.env.example"),
);
const manifest = [];
function walk(dir) {
  for (const item of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, item.name);
    if (item.isDirectory()) walk(p);
    else
      manifest.push({
        path: path.relative(target, p).split(path.sep).join("/"),
        sha256: crypto
          .createHash("sha256")
          .update(fs.readFileSync(p))
          .digest("hex"),
      });
  }
}
walk(target);
fs.writeFileSync(
  path.join(target, "manifest.json"),
  JSON.stringify(
    { version, created_at: new Date().toISOString(), files: manifest },
    null,
    2,
  ),
);
const archive = path.join(releases, "dmf-" + version + ".zip");
execFileSync("tar", ["-a", "-c", "-f", archive, "-C", target, "."], {
  stdio: "inherit",
});
const hash = crypto
  .createHash("sha256")
  .update(fs.readFileSync(archive))
  .digest("hex");
fs.writeFileSync(
  archive + ".sha256",
  hash + "  " + path.basename(archive) + "\n",
);
console.log("Release:", archive);
console.log("SHA256:", hash);
