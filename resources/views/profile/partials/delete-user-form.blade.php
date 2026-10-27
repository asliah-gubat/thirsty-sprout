<section class="space-y-6">
    <header>
        <h2 class="font-display text-xl font-bold text-forest">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-soil">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-danger-button type="button" id="open-user-deletion-dialog">
        {{ __('Delete Account') }}
    </x-danger-button>

    <dialog
        id="confirm-user-deletion"
        @if ($errors->userDeletion->isNotEmpty()) open @endif
        aria-labelledby="confirm-user-deletion-title"
        class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-xl bg-paper p-0 text-forest shadow-xl backdrop:bg-forest/75"
    >
        <div class="p-6">
            <h2 id="confirm-user-deletion-title" class="font-display text-xl font-bold text-forest">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-1 text-sm text-soil">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <form method="post" action="{{ route('profile.destroy') }}" class="mt-6">
            @csrf
            @method('delete')

                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />
                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full"
                    placeholder="{{ __('Password') }}"
                    autocomplete="current-password"
                    autofocus
                />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button type="button" data-close-user-deletion>
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button>
                        {{ __('Delete Account') }}
                    </x-danger-button>
                </div>
            </form>
        </div>
    </dialog>
</section>

@push('scripts')
<script>
const userDeletionDialog = document.getElementById('confirm-user-deletion');
document.getElementById('open-user-deletion-dialog').addEventListener('click', () => userDeletionDialog.showModal());
document.querySelector('[data-close-user-deletion]').addEventListener('click', () => userDeletionDialog.close());
userDeletionDialog.addEventListener('click', (event) => {
  if (event.target === userDeletionDialog) userDeletionDialog.close();
});
</script>
@endpush
