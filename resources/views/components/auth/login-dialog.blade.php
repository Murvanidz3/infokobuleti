{{-- Sign-in dialog for the header. Its form is mounted from the template on first open, so pages do
     not start the phone sign-in component until someone actually wants to sign in. --}}
<x-modal name="login" size="sm" :title="__('auth.login.title')" data-login-dialog>
    <p class="-mt-1 mb-5 text-[14.5px] text-secondary">{{ __('auth.login.subtitle') }}</p>
    <template data-login-panel>
        <x-auth.login-panel :return-to="request()->fullUrl()" />
    </template>
</x-modal>
