export interface Activity {
  id: string
  code: string
  title: string
  team: string
  pjk: string
  year: number
  status: 'DRAF' | 'AKTIF' | 'SELESAI' | 'ARSIP'
  targetAkhir: number
  realisasiKumulatif: number
  satuan: string
  progresAkhir: number
  capaianPeriode: number
  coverageRate: number
  periodeAktif: string
}

export interface RegionEvaluation {
  id: string
  code: string
  name: string
  targetAkhir: number
  periode1: {
    realisasi: number
    target: number
    attainment: number
    status: 'TERCAPAI' | 'DI_BAWAH_TARGET' | 'BELUM_ADA_DATA'
    isLate?: boolean
  }
  periode2: {
    realisasi: number
    target: number
    attainment: number
    status: 'TERCAPAI' | 'DI_BAWAH_TARGET' | 'BELUM_ADA_DATA'
    isLate?: boolean
  }
  periode3: {
    realisasi: number | null
    target: number
    attainment: number | null
    status: 'TERCAPAI' | 'DI_BAWAH_TARGET' | 'BELUM_ADA_DATA'
    isLate?: boolean
  }
  progresAkhir: number
  statusKeseluruhan: string
}

export interface BatchRow {
  regionId: string
  regionCode: string
  regionName: string
  targetPeriode: number
  realisasiLalu: number
  realisasiKini: number | string
  tanggalPosisi: string
  kendala: string
  status: 'saved' | 'idle' | 'invalid' | 'warning'
  errorMessage?: string
}

export interface AuditRecord {
  id: string
  ticketNo: string
  regionName: string
  activityTitle: string
  periodLabel: string
  oldValue: number
  newValue: number
  operator: string
  timestamp: string
  status: 'DISETUJUI' | 'MENUNGGU_REVIEW' | 'BUTUH_KLARIFIKASI'
  alasan: string
  suratRef?: string
}

export interface WidgetItem {
  id: string
  title: string
  desc: string
  icon: string
  tags: string[]
  installed: boolean
}
