<?php

use App\Enums\CompanyThemeMode;
use App\Models\CompanySetting;
use App\Services\CompanySettingsService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Company settings')] class extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public string $name = '';
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $website = null;
    public string $primaryColor = '#2563eb';
    public string $secondaryColor = '#0f172a';
    public string $sidebarColor = '#ffffff';
    public bool $loginBrandingEnabled = true;
    public ?string $loginHeading = null;
    public ?string $loginSubheading = null;
    public string $defaultLocale = 'ar';
    public string $themeMode = 'system';
    public $logoUpload = null;
    public $faviconUpload = null;

    public function mount(CompanySettingsService $companySettings): void
    {
        $company = Auth::user()->company;

        abort_if($company === null, 403);

        $settings = $companySettings->settingsFor($company);

        $this->authorize('view', $settings);

        $this->name = $company->name;
        $this->email = $company->email;
        $this->phone = $company->phone;
        $this->website = $company->website;
        $this->primaryColor = $settings->primary_color;
        $this->secondaryColor = $settings->secondary_color;
        $this->sidebarColor = $settings->sidebar_color;
        $this->loginBrandingEnabled = $settings->login_branding_enabled;
        $this->loginHeading = $settings->login_heading;
        $this->loginSubheading = $settings->login_subheading;
        $this->defaultLocale = $settings->default_locale;
        $this->themeMode = $settings->theme_mode?->value ?? CompanyThemeMode::System->value;
    }

    public function updateCompanyProfile(CompanySettingsService $companySettings): void
    {
        $company = Auth::user()->company;
        $settings = $companySettings->settingsFor($company);

        $this->authorize('update', $settings);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $companySettings->updateCompanyProfile($company, $validated);

        Flux::toast(variant: 'success', text: __('Company profile updated.'));
    }

    public function updatePresentation(CompanySettingsService $companySettings): void
    {
        $company = Auth::user()->company;
        $settings = $companySettings->settingsFor($company);

        $this->authorize('update', $settings);
        $this->authorize('updateTheme', $settings);
        $this->authorize('updateLanguage', $settings);

        $validated = $this->validate([
            'primaryColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondaryColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sidebarColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'loginBrandingEnabled' => ['required', 'boolean'],
            'loginHeading' => ['nullable', 'string', 'max:255'],
            'loginSubheading' => ['nullable', 'string', 'max:255'],
            'defaultLocale' => ['required', 'string', 'in:ar,en'],
            'themeMode' => ['required', 'string', 'in:light,dark,system'],
        ]);

        $companySettings->updatePresentation($company, [
            'primary_color' => $validated['primaryColor'],
            'secondary_color' => $validated['secondaryColor'],
            'sidebar_color' => $validated['sidebarColor'],
            'login_branding_enabled' => $validated['loginBrandingEnabled'],
            'login_heading' => $validated['loginHeading'],
            'login_subheading' => $validated['loginSubheading'],
            'default_locale' => $validated['defaultLocale'],
            'theme_mode' => $validated['themeMode'],
        ]);

        Flux::toast(variant: 'success', text: __('Company presentation updated.'));
    }

    public function updateLogo(CompanySettingsService $companySettings): void
    {
        $company = Auth::user()->company;
        $settings = $companySettings->settingsFor($company);

        $this->authorize('update', $settings);

        $validated = $this->validate([
            'logoUpload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64,max_width=2048,max_height=2048'],
        ]);

        $companySettings->updateLogo($company, $validated['logoUpload']);
        $this->reset('logoUpload');

        Flux::toast(variant: 'success', text: __('Logo updated.'));
    }

    public function updateFavicon(CompanySettingsService $companySettings): void
    {
        $company = Auth::user()->company;
        $settings = $companySettings->settingsFor($company);

        $this->authorize('update', $settings);

        $validated = $this->validate([
            'faviconUpload' => ['required', 'image', 'mimes:png', 'max:512', 'dimensions:min_width=32,min_height=32,max_width=512,max_height=512'],
        ]);

        $companySettings->updateFavicon($company, $validated['faviconUpload']);
        $this->reset('faviconUpload');

        Flux::toast(variant: 'success', text: __('Favicon updated.'));
    }

    #[Computed]
    public function settings(): CompanySetting
    {
        return app(CompanySettingsService::class)->settingsFor(Auth::user()->company);
    }

    #[Computed]
    public function logoUrl(): ?string
    {
        return $this->settings->logo_path === null ? null : Storage::disk('public')->url($this->settings->logo_path);
    }

    #[Computed]
    public function faviconUrl(): ?string
    {
        return $this->settings->favicon_path === null ? null : Storage::disk('public')->url($this->settings->favicon_path);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Company')" :subheading="__('Manage company presentation and localization')">
        <form wire:submit="updateCompanyProfile" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Company name')" type="text" required />
            <flux:input wire:model="email" :label="__('Company email')" type="email" />
            <flux:input wire:model="phone" :label="__('Phone')" type="text" />
            <flux:input wire:model="website" :label="__('Website')" type="url" />

            <flux:button variant="primary" type="submit">{{ __('Save company') }}</flux:button>
        </form>

        <form wire:submit="updatePresentation" class="my-6 w-full space-y-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="primaryColor" :label="__('Primary color')" type="color" />
                <flux:input wire:model="secondaryColor" :label="__('Secondary color')" type="color" />
                <flux:input wire:model="sidebarColor" :label="__('Sidebar color')" type="color" />
            </div>

            <flux:checkbox wire:model="loginBrandingEnabled" :label="__('Show company branding on login')" />
            <flux:input wire:model="loginHeading" :label="__('Login heading')" type="text" />
            <flux:input wire:model="loginSubheading" :label="__('Login subheading')" type="text" />

            <flux:select wire:model="defaultLocale" :label="__('Default language')">
                <flux:select.option value="ar">{{ __('Arabic') }}</flux:select.option>
                <flux:select.option value="en">{{ __('English') }}</flux:select.option>
            </flux:select>

            <flux:radio.group wire:model="themeMode" variant="segmented" :label="__('Company theme')">
                <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
            </flux:radio.group>

            <flux:button variant="primary" type="submit">{{ __('Save presentation') }}</flux:button>
        </form>

        <form wire:submit="updateLogo" class="my-6 w-full space-y-4">
            @if ($this->logoUrl)
                <img src="{{ $this->logoUrl }}" alt="{{ $name }}" class="h-12 w-auto rounded border border-zinc-200 object-contain dark:border-zinc-700">
            @endif
            <flux:input wire:model="logoUpload" :label="__('Logo')" type="file" accept="image/jpeg,image/png,image/webp" />
            <flux:button variant="primary" type="submit">{{ __('Upload logo') }}</flux:button>
        </form>

        <form wire:submit="updateFavicon" class="my-6 w-full space-y-4">
            @if ($this->faviconUrl)
                <img src="{{ $this->faviconUrl }}" alt="{{ __('Favicon') }}" class="size-10 rounded border border-zinc-200 object-contain dark:border-zinc-700">
            @endif
            <flux:input wire:model="faviconUpload" :label="__('Favicon')" type="file" accept="image/png" />
            <flux:button variant="primary" type="submit">{{ __('Upload favicon') }}</flux:button>
        </form>
    </x-pages::settings.layout>
</section>
