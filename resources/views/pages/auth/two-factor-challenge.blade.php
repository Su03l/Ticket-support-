<x-layouts::auth :title="__('Two-factor authentication')">
  <div class="flex flex-col gap-8">
    <div
      class="relative w-full h-auto"
      x-cloak
      x-data="{
        showRecoveryInput: @js($errors->has('recovery_code')),
        code: '',
        recovery_code: '',
        focusOtp() {
          this.$nextTick(() => this.$refs.otp?.querySelector('input')?.focus());
        },
        init() {
          if (! this.showRecoveryInput) {
            this.focusOtp();
          }
        },
        toggleInput() {
          this.showRecoveryInput = !this.showRecoveryInput;

          this.code = '';
          this.recovery_code = '';

          $nextTick(() => {
            this.showRecoveryInput
              ? this.$refs.recovery_code?.focus()
              : this.focusOtp();
          });
        },
      }"
    >
      <div x-show="!showRecoveryInput">
        <x-auth-header
          :title="__('Authentication code')"
          :description="__('Enter the authentication code provided by your authenticator application.')"
        />
      </div>

      <div x-show="showRecoveryInput">
        <x-auth-header
          :title="__('Recovery code')"
          :description="__('Please confirm access to your account by entering one of your emergency recovery codes.')"
        />
      </div>

      <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-8">
        @csrf

        <div class="space-y-6 text-center">
          <div x-show="!showRecoveryInput">
            <div class="flex items-center justify-center my-6" x-ref="otp">
              <flux:otp
                x-model="code"
                length="6"
                name="code"
                label="OTP Code"
                label:sr-only
                class="mx-auto"
               />
            </div>
          </div>

          <div x-show="showRecoveryInput">
            <div class="my-6">
              <flux:input
                type="text"
                name="recovery_code"
                x-ref="recovery_code"
                x-bind:required="showRecoveryInput"
                autocomplete="one-time-code"
                x-model="recovery_code"
                class="text-center font-mono tracking-widest"
                placeholder="00000-00000"
              />
            </div>

            @error('recovery_code')
              <flux:text color="red" class="font-semibold text-sm">
                {{ $message }}
              </flux:text>
            @enderror
          </div>

          <flux:button
            variant="primary"
            type="submit"
            class="w-full font-bold py-3 rounded-xl shadow-lg shadow-zinc-200/50 dark:shadow-none"
          >
            {{ __('Continue') }}
          </flux:button>
        </div>

        <div class="mt-8 space-x-1 text-sm font-medium leading-5 text-center text-zinc-500">
          <span class="opacity-70">{{ __('or you can') }}</span>
          <div class="inline font-bold underline cursor-pointer hover:text-zinc-900 dark:hover:text-white transition-colors">
            <span x-show="!showRecoveryInput" @click="toggleInput()">{{ __('login using a recovery code') }}</span>
            <span x-show="showRecoveryInput" @click="toggleInput()">{{ __('login using an authentication code') }}</span>
          </div>
        </div>
      </form>
    </div>
  </div>
</x-layouts::auth>