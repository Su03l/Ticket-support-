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

<x-pages::settings.layout :heading="__('Company')":subheading="__('Manage company presentation and localization')">
 {{-- Company Profile Card --}}
 <flux:card class="space-y-6">
  <div>
   <flux:heading size="lg">{{ __('Company profile') }}</flux:heading>
   <flux:subheading>{{ __('Basic identification and contact details for your organization.') }}</flux:subheading>
  </div>

  <form wire:submit="updateCompanyProfile" class="space-y-6">
   <div class="grid gap-6 sm:grid-cols-2">
    <flux:input wire:model="name":label="__('Company name')" type="text"required icon="building-office"/>
    <flux:input wire:model="email":label="__('Company email')" type="email" icon="envelope"/>
    <flux:input wire:model="phone":label="__('Phone number')" type="text" icon="phone"/>
    <flux:input wire:model="website":label="__('Website URL')" type="url" icon="globe-alt"/>
   </div>

   <div class="flex justify-end pt-4 border-t border-zinc-100 dark:border-zinc-800">
    <flux:button variant="primary" type="submit" icon="check">{{ __('Save Profile') }}</flux:button>
   </div>
  </form>
 </flux:card>

 {{-- Presentation & Branding Card --}}
 <flux:card class="space-y-8">
  <div>
   <flux:heading size="lg">{{ __('Presentation & Branding') }}</flux:heading>
   <flux:subheading>{{ __('Customize the look and feel of your support portal and login experience.') }}</flux:subheading>
  </div>

  <form wire:submit="updatePresentation" class="space-y-8">
   <div class="grid gap-6 sm:grid-cols-3">
    <flux:field>
     <flux:label>{{ __('Primary color') }}</flux:label>
     <div class="flex items-center gap-3">
      <flux:input wire:model="primaryColor" type="color" class="!w-12 !h-10 !p-1 !rounded-lg"/>
      <flux:text size="sm"font="mono" class="bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">{{ $primaryColor }}</flux:text>
     </div>
    </flux:field>
    <flux:field>
     <flux:label>{{ __('Secondary color') }}</flux:label>
     <div class="flex items-center gap-3">
      <flux:input wire:model="secondaryColor" type="color" class="!w-12 !h-10 !p-1 !rounded-lg"/>
      <flux:text size="sm"font="mono" class="bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">{{ $secondaryColor }}</flux:text>
     </div>
    </flux:field>
    <flux:field>
     <flux:label>{{ __('Sidebar color') }}</flux:label>
     <div class="flex items-center gap-3">
      <flux:input wire:model="sidebarColor" type="color" class="!w-12 !h-10 !p-1 !rounded-lg"/>
      <flux:text size="sm"font="mono" class="bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">{{ $sidebarColor }}</flux:text>
     </div>
    </flux:field>
   </div>

   <div class="space-y-6 rounded-2xl border border-zinc-200 bg-zinc-50/50 p-6 dark:border-zinc-800 dark:bg-zinc-900/30">
    <flux:checkbox wire:model="loginBrandingEnabled":label="__('Show company branding on login')"description="{{ __('Toggle visibility of logo and heading on the login page') }}"/>
    
    <div class="grid gap-6 sm:grid-cols-2 pt-2">
     <flux:input wire:model="loginHeading":label="__('Login heading')" type="text"placeholder="{{ __('Welcome to our portal') }}"/>
     <flux:input wire:model="loginSubheading":label="__('Login subheading')" type="text"placeholder="{{ __('Please sign in to continue') }}"/>
    </div>
   </div>

   <div class="grid gap-8 sm:grid-cols-2">
    <flux:select wire:model="defaultLocale":label="__('Default Language')" icon="language">
     <flux:select.option value="ar">{{ __('Arabic') }}</flux:select.option>
     <flux:select.option value="en">{{ __('English') }}</flux:select.option>
    </flux:select>

    <div class="space-y-3">
     <flux:label>{{ __('Default Theme Mode') }}</flux:label>
     <flux:radio.group wire:model="themeMode" variant="segmented">
      <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
      <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
      <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
     </flux:radio.group>
    </div>
   </div>

   <div class="flex justify-end pt-4 border-t border-zinc-100 dark:border-zinc-800">
    <flux:button variant="primary" type="submit" icon="check">{{ __('Save Branding') }}</flux:button>
   </div>
  </form>
 </flux:card>

 {{-- Assets Card --}}
 <flux:card class="space-y-6">
  <div>
   <flux:heading size="lg">{{ __('Logo & Favicon') }}</flux:heading>
   <flux:subheading>{{ __('Upload high-quality images for your company branding.') }}</flux:subheading>
  </div>

  <div class="grid gap-8 sm:grid-cols-2">
   <form wire:submit="updateLogo" class="space-y-6 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800 bg-zinc-50/10">
    <div class="flex items-center justify-between">
     <flux:label font="medium">{{ __('Company Logo') }}</flux:label>
     @if ($this->logoUrl)
      <div class="bg-white dark:bg-zinc-800 p-2 rounded-lg border border-zinc-200 dark:border-zinc-700">
       <img src="{{ $this->logoUrl }}"alt="{{ $name }}" class="h-8 w-auto object-contain">
      </div>
     @endif
    </div>
    <flux:input wire:model="logoUpload" type="file"accept="image/jpeg,image/png,image/webp"/>
    <div class="flex justify-end">
     <flux:button variant="primary" type="submit" size="sm" icon="cloud-arrow-up">{{ __('Upload Logo') }}</flux:button>
    </div>
   </form>

   <form wire:submit="updateFavicon" class="space-y-6 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800 bg-zinc-50/10">
    <div class="flex items-center justify-between">
     <flux:label font="medium">{{ __('Favicon') }}</flux:label>
     @if ($this->faviconUrl)
      <div class="bg-white dark:bg-zinc-800 p-2 rounded-lg border border-zinc-200 dark:border-zinc-700">
       <img src="{{ $this->faviconUrl }}"alt="{{ __('Favicon') }}" class="size-6 object-contain">
      </div>
     @endif
    </div>
    <flux:input wire:model="faviconUpload" type="file"accept="image/png"/>
    <div class="flex justify-end">
     <flux:button variant="primary" type="submit" size="sm" icon="cloud-arrow-up">{{ __('Upload Favicon') }}</flux:button>
    </div>
   </form>
  </div>
 </flux:card>
</x-pages::settings.layout>
