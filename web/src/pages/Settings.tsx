import { Cog6ToothIcon } from '@heroicons/react/24/outline'

export default function Settings() {
  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Paramètres</h1>
        <p className="text-sm text-gray-500 mt-1">Configuration de l'établissement</p>
      </div>

      <div className="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div className="flex flex-col items-center justify-center py-16 text-center">
          <Cog6ToothIcon className="w-12 h-12 text-gray-300 mb-3" />
          <p className="text-gray-500 font-medium">Page en cours de développement</p>
          <p className="text-sm text-gray-400 mt-1">Les paramètres seront disponibles prochainement</p>
        </div>
      </div>
    </div>
  )
}
