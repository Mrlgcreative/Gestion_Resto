import { useEffect, useState } from 'react'
import client from '../api/client'
import { ChartBarIcon } from '@heroicons/react/24/outline'

interface SalesReport {
  summary: {
    total_orders: number
    paid_orders: number
    canceled_orders: number
    total_revenue: number
    average_order: number
  }
  top_products: Array<{
    product: { name: string } | null
    total_qty: number
    total_revenue: number
  }>
}

export default function Reports() {
  const [report, setReport] = useState<SalesReport | null>(null)

  useEffect(() => {
    client.get('/reports/sales', {
      params: { date_from: '2026-01-01', date_to: '2026-12-31' },
    }).then(({ data }) => setReport(data))
  }, [])

  if (!report) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-gray-400 text-sm">Chargement...</div>
      </div>
    )
  }

  const { summary } = report

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Rapports</h1>
        <p className="text-sm text-gray-500 mt-1">Analyse des ventes et performances</p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {[
          { label: 'Total commandes', value: summary.total_orders, color: 'text-gray-900' },
          { label: 'Payées', value: summary.paid_orders, color: 'text-emerald-600' },
          { label: 'Revenu total', value: `${Number(summary.total_revenue).toLocaleString('fr-FR')} $`, color: 'text-blue-600' },
          { label: 'Panier moyen', value: `${Number(summary.average_order).toLocaleString('fr-FR', { minimumFractionDigits: 2 })} $`, color: 'text-indigo-600' },
        ].map((stat) => (
          <div key={stat.label} className="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p className="text-sm text-gray-500 mb-1">{stat.label}</p>
            <p className={`text-2xl font-bold ${stat.color}`}>{stat.value}</p>
          </div>
        ))}
      </div>

      <div className="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div className="px-6 py-4 border-b border-gray-100">
          <h2 className="text-lg font-semibold text-gray-900">Top produits</h2>
        </div>
        {report.top_products.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-16 text-center">
            <ChartBarIcon className="w-12 h-12 text-gray-300 mb-3" />
            <p className="text-gray-500 font-medium">Aucune donnée</p>
            <p className="text-sm text-gray-400 mt-1">Les produits populaires apparaîtront ici</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-100 bg-gray-50">
                  <th className="text-left px-6 py-3 font-medium text-gray-600">Produit</th>
                  <th className="text-right px-6 py-3 font-medium text-gray-600">Quantité vendue</th>
                  <th className="text-right px-6 py-3 font-medium text-gray-600">Revenu</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {report.top_products.map((p, i) => (
                  <tr key={i} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-3 font-medium text-gray-900">{p.product?.name || 'N/A'}</td>
                    <td className="px-6 py-3 text-right text-gray-600">{p.total_qty}</td>
                    <td className="px-6 py-3 text-right font-medium text-gray-900">
                      {Number(p.total_revenue).toLocaleString('fr-FR')} $
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  )
}
