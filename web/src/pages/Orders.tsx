import { useEffect, useState } from 'react'
import client from '../api/client'
import { ClipboardDocumentListIcon } from '@heroicons/react/24/outline'

interface Order {
  id: number
  table_number: string | null
  total_amount: number
  currency: string
  status: string
  created_at: string
  user: { name: string }
  items_count?: number
}

const statusStyles: Record<string, string> = {
  paid: 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
  pending: 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
  canceled: 'bg-red-100 text-red-700 ring-1 ring-red-200',
}

const statusLabels: Record<string, string> = {
  paid: 'Payée',
  pending: 'En attente',
  canceled: 'Annulée',
}

export default function Orders() {
  const [orders, setOrders] = useState<Order[]>([])

  useEffect(() => {
    client.get('/orders?per_page=20').then(({ data }) => setOrders(data.data || []))
  }, [])

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Commandes</h1>
        <p className="text-sm text-gray-500 mt-1">Toutes les commandes de l'établissement</p>
      </div>

      <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        {orders.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-16 text-center">
            <ClipboardDocumentListIcon className="w-12 h-12 text-gray-300 mb-3" />
            <p className="text-gray-500 font-medium">Aucune commande</p>
            <p className="text-sm text-gray-400 mt-1">Les commandes apparaîtront ici</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-200 bg-gray-50">
                  <th className="text-left px-4 py-3 font-medium text-gray-600">#</th>
                  <th className="text-left px-4 py-3 font-medium text-gray-600">Table</th>
                  <th className="text-left px-4 py-3 font-medium text-gray-600">Caissier</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">Total</th>
                  <th className="text-center px-4 py-3 font-medium text-gray-600">Statut</th>
                  <th className="text-left px-4 py-3 font-medium text-gray-600">Date</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {orders.map((o) => (
                  <tr key={o.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-4 py-3 font-medium text-gray-900">#{o.id}</td>
                    <td className="px-4 py-3 text-gray-600">{o.table_number || '-'}</td>
                    <td className="px-4 py-3 text-gray-600">{o.user.name}</td>
                    <td className="px-4 py-3 text-right font-medium text-gray-900">
                      {Number(o.total_amount).toLocaleString('fr-FR')} {o.currency || '$'}
                    </td>
                    <td className="px-4 py-3 text-center">
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusStyles[o.status] || 'bg-gray-100 text-gray-700'}`}>
                        {statusLabels[o.status] || o.status}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-gray-500 text-xs">
                      {new Date(o.created_at).toLocaleDateString('fr-FR', {
                        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
                      })}
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
