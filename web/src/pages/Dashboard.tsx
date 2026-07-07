import { useEffect, useState } from 'react'
import client from '../api/client'
import {
  CurrencyDollarIcon,
  ShoppingCartIcon,
  CubeIcon,
  ClockIcon,
  UserGroupIcon,
  ExclamationTriangleIcon,
} from '@heroicons/react/24/outline'

interface Stats {
  today_orders: number
  today_revenue: number
  active_products: number
  pending_orders: number
  active_users: number
}

type Variant = 'blue' | 'emerald' | 'indigo' | 'amber' | 'purple'

const statCards: { label: string; key: keyof Stats; icon: React.ComponentType<React.SVGProps<SVGSVGElement>>; format: string; variant: Variant }[] = [
  { label: "Ventes du jour", key: 'today_revenue', icon: CurrencyDollarIcon, format: 'currency', variant: 'blue' },
  { label: "Commandes du jour", key: 'today_orders', icon: ShoppingCartIcon, format: 'number', variant: 'emerald' },
  { label: "Produits actifs", key: 'active_products', icon: CubeIcon, format: 'number', variant: 'indigo' },
  { label: "En attente", key: 'pending_orders', icon: ClockIcon, format: 'number', variant: 'amber' },
  { label: "Utilisateurs actifs", key: 'active_users', icon: UserGroupIcon, format: 'number', variant: 'purple' },
]

const variants: Record<Variant, { bg: string; icon: string; value: string }> = {
  blue: { bg: 'bg-blue-50', icon: 'text-blue-600', value: 'text-blue-900' },
  emerald: { bg: 'bg-emerald-50', icon: 'text-emerald-600', value: 'text-emerald-900' },
  indigo: { bg: 'bg-indigo-50', icon: 'text-indigo-600', value: 'text-indigo-900' },
  amber: { bg: 'bg-amber-50', icon: 'text-amber-600', value: 'text-amber-900' },
  purple: { bg: 'bg-purple-50', icon: 'text-purple-600', value: 'text-purple-900' },
}

export default function Dashboard() {
  const [stats, setStats] = useState<Stats | null>(null)

  useEffect(() => {
    client.get('/dashboard/stats').then(({ data }) => setStats(data))
  }, [])

  if (!stats) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-gray-400 text-sm">Chargement...</div>
      </div>
    )
  }

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-sm text-gray-500 mt-1">Aperçu de votre établissement</p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        {statCards.map((card) => {
          const v = variants[card.variant]
          const value = stats[card.key]
          const displayValue = card.format === 'currency'
            ? `${Number(value).toLocaleString('fr-FR')} $`
            : Number(value).toLocaleString('fr-FR')

          return (
            <div key={card.key} className="bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:shadow-md transition-shadow">
              <div className="flex items-center justify-between mb-3">
                <div className={`w-10 h-10 rounded-lg ${v.bg} flex items-center justify-center`}>
                  <card.icon className={`w-5 h-5 ${v.icon}`} />
                </div>
              </div>
              <p className="text-2xl font-bold text-gray-900">{displayValue}</p>
              <p className="text-sm text-gray-500 mt-1">{card.label}</p>
            </div>
          )
        })}
      </div>

      {stats.pending_orders > 0 && (
        <div className="mt-6 bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center gap-3">
          <ExclamationTriangleIcon className="w-5 h-5 text-amber-600 shrink-0" />
          <p className="text-sm text-amber-800">
            {stats.pending_orders} commande{stats.pending_orders > 1 ? 's' : ''} en attente de paiement
          </p>
        </div>
      )}
    </div>
  )
}
