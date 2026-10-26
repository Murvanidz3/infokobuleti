<x-layout.app :noindex="true" :title="$editType ? __('account.wizard.edit_title') : __('account.wizard.title')">
    <section class="min-h-[70vh] bg-surface-alt pt-5 pb-28 md:pt-8 md:pb-16">
        <div class="container-page">
            <livewire:listing-wizard :edit-type="$editType" :edit-id="$editId" />
        </div>
    </section>
</x-layout.app>
