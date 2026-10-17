/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        "brand-navy": "#1A3263",
        "brand-navy-subtle": "#EDF2FA",
        "brand-navy-dark": "#0F1A30",
        "brand-slate": "#547792",
        "brand-slate-light": "#7A9AB5",
        "brand-slate-subtle": "#F0F4F8",
        "brand-amber": "#FFC570",
        "brand-amber-deep": "#E6A74B",
        "brand-cream": "#EFD2B0",
        "brand-cream-soft": "#FAF4ED",
        
        "background": "#F6F8FB",
        "border-card": "#EAEFF5",
        "text-title": "#0F1A30",
        "text-body": "#3E4B5E",
        "text-muted": "#7E8D9F",
        
        // Semantic statuses
        "status-tercapai-bg": "#EAF8EE",
        "status-tercapai-text": "#1E824C",
        "status-tercapai-border": "#B7E4C7",
        "status-dibawah-bg": "#FFF7EC",
        "status-dibawah-text": "#B45309",
        "status-dibawah-border": "#FFC570",
        "status-kosong-bg": "#FEEEEE",
        "status-kosong-text": "#C53030",
        "status-kosong-border": "#FCA5A5",
        "status-terjadwal-bg": "#EDF3F9",
        "status-terjadwal-text": "#2B6CB0",
        "status-terjadwal-border": "#BEE3F8",

        // Stitch compatibility
        "surface-container-lowest": "#FFFFFF",
        "surface-container-low": "#F2F4F7",
        "surface-container": "#ECEEF1",
        "tertiary-fixed": "#FFDDB2",
        "on-tertiary-fixed": "#291800",
      },
      fontFamily: {
        outfit: ["Outfit", "sans-serif"],
        manrope: ["Manrope", "sans-serif"],
      },
      borderRadius: {
        "card": "18px",
      },
      boxShadow: {
        "card": "0 4px 20px -2px rgba(26, 50, 99, 0.05)",
        "card-hover": "0 10px 25px -4px rgba(26, 50, 99, 0.09)",
        "drawer": "-12px 0 35px -5px rgba(26, 50, 99, 0.12)",
        "orb-glow": "0 8px 28px rgba(255, 197, 112, 0.45)",
      }
    },
  },
  plugins: [],
}
