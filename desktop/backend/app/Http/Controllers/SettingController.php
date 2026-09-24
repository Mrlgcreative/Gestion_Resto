<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings.manage'),
        ];
    }

    public function index()
    {
        $settings = Setting::instance();
        $currencies = Currency::orderBy('name')->get();
        $exchangeRates = ExchangeRate::with('currency')
            ->where('is_active', true)
            ->get();
        $users = User::with('role')->orderBy('name')->get();
        $roles = Role::with('permissions')->orderBy('name')->get();

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
            'currencies' => $currencies,
            'exchangeRates' => $exchangeRates,
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'restaurant_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'rccm' => ['nullable', 'string', 'max:100'],
            'id_nat' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
        ]);

        $settings = Setting::instance();
        $settings->update($validated);

        ActivityLog::log('settings_updated', 'Setting', ['changes' => array_keys($validated)]);

        return back()->with('success', 'Informations du restaurant mises à jour.');
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $settings = Setting::instance();

        // Delete old logo
        if ($settings->logo) {
            Storage::disk('public')->delete($settings->logo);
        }

        $path = $request->file('logo')->store('settings', 'public');
        $settings->update(['logo' => $path]);

        return back()->with('success', 'Logo mis à jour.');
    }

    public function updateOrderOptions(Request $request)
    {
        $validated = $request->validate([
            'orders_enabled' => ['required', 'boolean'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'service_charge' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $settings = Setting::instance();
        $settings->update($validated);

        ActivityLog::log('order_options_updated', 'Setting', $validated);

        return back()->with('success', 'Options des commandes mises à jour.');
    }

    public function updateExchangeOptions(Request $request)
    {
        $validated = $request->validate([
            'exchange_rate_enabled' => ['required', 'boolean'],
            'auto_update_prices' => ['boolean'],
        ]);

        $settings = Setting::instance();
        $settings->update($validated);

        ActivityLog::log('exchange_options_updated', 'Setting', $validated);

        return back()->with('success', 'Options de change mises à jour.');
    }

    public function setDefaultCurrency(Request $request)
    {
        $validated = $request->validate([
            'currency_id' => ['required', 'exists:currencies,id'],
        ]);

        $currency = Currency::findOrFail($validated['currency_id']);
        Currency::setDefault($currency);
        
        Setting::instance()->update(['default_currency_id' => $currency->id]);

        ActivityLog::log('default_currency_changed', 'Currency', ['currency' => $currency->code]);

        return back()->with('success', 'Devise par défaut mise à jour.');
    }

    public function storeCurrency(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:3', 'unique:currencies,code'],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:10'],
            'is_default' => ['boolean'],
        ]);

        $currency = Currency::create($validated);

        if ($validated['is_default'] ?? false) {
            Currency::setDefault($currency);
            Setting::instance()->update(['default_currency_id' => $currency->id]);
        }

        ActivityLog::logCreation('Currency', $currency->id);

        return back()->with('success', 'Devise créée avec succès.');
    }

    public function updateCurrency(Request $request, Currency $currency)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:10'],
            'is_default' => ['boolean'],
        ]);

        $currency->update($validated);

        if ($validated['is_default'] ?? false) {
            Currency::setDefault($currency);
            Setting::instance()->update(['default_currency_id' => $currency->id]);
        }

        ActivityLog::logUpdate('Currency', $currency->id);

        return back()->with('success', 'Devise mise à jour avec succès.');
    }

    public function deleteCurrency(Currency $currency)
    {
        if ($currency->is_default) {
            return back()->with('error', 'La devise par défaut ne peut pas être supprimée.');
        }

        ActivityLog::logDeletion('Currency', $currency->id);
        $currency->delete();

        return back()->with('success', 'Devise supprimée avec succès.');
    }

    public function storeExchangeRate(Request $request)
    {
        $validated = $request->validate([
            'currency_id' => ['required', 'exists:currencies,id'],
            'rate' => ['required', 'numeric', 'min:0.0001'],
        ]);

        // Deactivate previous rate for this currency
        ExchangeRate::where('currency_id', $validated['currency_id'])
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $rate = ExchangeRate::create([
            'currency_id' => $validated['currency_id'],
            'rate' => $validated['rate'],
            'is_active' => true,
        ]);

        // Auto-update product prices if enabled
        $settings = Setting::instance();
        if ($settings->auto_update_prices) {
            $this->updateProductPrices();
        }

        ActivityLog::logCreation('ExchangeRate', $rate->id);

        return back()->with('success', 'Taux de change enregistré.');
    }

    public function updateExchangeRate(Request $request, ExchangeRate $exchangeRate)
    {
        $validated = $request->validate([
            'rate' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $exchangeRate->update($validated);

        // Auto-update product prices if enabled
        $settings = Setting::instance();
        if ($settings->auto_update_prices) {
            $this->updateProductPrices();
        }

        ActivityLog::logUpdate('ExchangeRate', $exchangeRate->id);

        return back()->with('success', 'Taux de change mis à jour.');
    }

    public function deleteExchangeRate(ExchangeRate $exchangeRate)
    {
        ActivityLog::logDeletion('ExchangeRate', $exchangeRate->id);
        $exchangeRate->delete();

        return back()->with('success', 'Taux de change supprimé.');
    }

    public function updateProductPrices()
    {
        $defaultCurrency = Currency::default();
        if (!$defaultCurrency) {
            return back()->with('error', 'Aucune devise par défaut définie.');
        }

        $rate = $defaultCurrency->activeRate();
        if (!$rate) {
            return back()->with('error', 'Aucun taux de change actif pour la devise par défaut.');
        }

        // Update all product selling prices based on base_price and exchange rate
        Product::query()->update([
            'selling_price' => \DB::raw("base_price * {$rate->rate}"),
        ]);

        ActivityLog::log('prices_updated', 'Product', ['rate' => $rate->rate]);

        return back()->with('success', 'Prix des produits mis à jour avec le taux de change.');
    }

    // User Management
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        ActivityLog::logCreation('User', $user->id);

        return back()->with('success', 'Utilisateur créé avec succès.');
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        ActivityLog::logUpdate('User', $user->id);

        return back()->with('success', 'Utilisateur mis à jour.');
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        ActivityLog::logDeletion('User', $user->id);
        $user->delete();

        return back()->with('success', 'Utilisateur supprimé.');
    }
}
