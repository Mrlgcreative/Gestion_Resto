<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de Session Cuisine</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 24px;
            color: #e74c3c;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 12px;
            color: #666;
        }
        .session-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
        }
        .session-info table {
            width: 100%;
        }
        .session-info td {
            padding: 5px;
        }
        .session-info td:first-child {
            font-weight: bold;
            width: 150px;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }
        .stat-box {
            display: table-cell;
            background: #fff;
            border: 1px solid #dee2e6;
            padding: 15px;
            text-align: center;
            width: 25%;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #e74c3c;
            display: block;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        .category-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .category-header {
            background: #e74c3c;
            color: white;
            padding: 10px 15px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 5px 5px 0 0;
        }
        .category-items {
            border: 1px solid #dee2e6;
            border-top: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: #f8f9fa;
        }
        th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #dee2e6;
            font-size: 10px;
            text-transform: uppercase;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        tbody tr:hover {
            background: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .category-summary {
            background: #f8f9fa;
            padding: 10px 15px;
            font-weight: bold;
            border-top: 2px solid #dee2e6;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .notes {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .notes h3 {
            font-size: 12px;
            margin-bottom: 8px;
            color: #856404;
        }
        .notes p {
            font-size: 11px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 RAPPORT DE SESSION CUISINE</h1>
        <p>Session #{{ $session->id }} - {{ $session->opened_at->format('d/m/Y') }}</p>
    </div>

    <div class="session-info">
        <table>
            <tr>
                <td>Cuisinier :</td>
                <td><strong>{{ $session->user->name }}</strong></td>
            </tr>
            <tr>
                <td>Date d'ouverture :</td>
                <td>{{ $session->opened_at->format('d/m/Y à H:i') }}</td>
            </tr>
            <tr>
                <td>Date de fermeture :</td>
                <td>{{ $session->closed_at ? $session->closed_at->format('d/m/Y à H:i') : 'Session en cours' }}</td>
            </tr>
            <tr>
                <td>Durée totale :</td>
                <td><strong>{{ $total_duration }}</strong></td>
            </tr>
            <tr>
                <td>Statut :</td>
                <td>
                    <strong style="color: {{ $session->status === 'open' ? '#28a745' : '#6c757d' }}">
                        {{ $session->status === 'open' ? 'OUVERTE' : 'FERMÉE' }}
                    </strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="stats-grid">
        <div class="stat-box">
            <span class="stat-value">{{ $stats['total_orders_completed'] }}</span>
            <span class="stat-label">Commandes<br>Complétées</span>
        </div>
        <div class="stat-box">
            <span class="stat-value">{{ $stats['total_items_prepared'] }}</span>
            <span class="stat-label">Plats<br>Préparés</span>
        </div>
        <div class="stat-box">
            <span class="stat-value">{{ number_format($stats['average_preparation_time'], 0) }} min</span>
            <span class="stat-label">Temps Moyen<br>de Préparation</span>
        </div>
        <div class="stat-box">
            <span class="stat-value">
                {{ $stats['min_preparation_time'] }} - {{ $stats['max_preparation_time'] }} min
            </span>
            <span class="stat-label">Min - Max<br>Préparation</span>
        </div>
    </div>

    @if($session->notes)
    <div class="notes">
        <h3>📝 Notes de session</h3>
        <p>{{ $session->notes }}</p>
    </div>
    @endif

    <h2 style="margin-bottom: 20px; color: #e74c3c; font-size: 16px;">DÉTAIL DES PLATS PRÉPARÉS PAR CATÉGORIE</h2>

    @foreach($itemsByCategory as $categoryName => $categoryData)
    <div class="category-section">
        <div class="category-header">
            🍽️ {{ $categoryName }}
        </div>
        <div class="category-items">
            <table>
                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Table</th>
                        <th>Plat</th>
                        <th class="text-center">Qté</th>
                        <th class="text-center">Commandé</th>
                        <th class="text-center">Prêt</th>
                        <th class="text-right">Temps Prép.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categoryData['items'] as $item)
                    <tr>
                        <td>#{{ $item['order_number'] }}</td>
                        <td>Table {{ $item['table_number'] }}</td>
                        <td>{{ $item['product_name'] }}</td>
                        <td class="text-center">{{ $item['quantity'] }}</td>
                        <td class="text-center">{{ $item['created_at'] }}</td>
                        <td class="text-center">{{ $item['ready_at'] }}</td>
                        <td class="text-right">{{ $item['preparation_time'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="category-summary">
                Total pour cette catégorie : {{ $categoryData['total_quantity'] }} plat(s)
            </div>
        </div>
    </div>
    @endforeach

    <div class="footer">
        <p>Rapport généré le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>Gestion Resto - Système de Gestion de Restaurant</p>
    </div>
</body>
</html>
