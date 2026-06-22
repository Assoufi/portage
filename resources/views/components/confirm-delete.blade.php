{{-- resources/views/components/confirm-delete.blade.php --}}
<div x-data="confirmDelete()" 
     x-show="show" 
     x-transition.opacity
     class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
     style="display: none;">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-4" x-text="title"></h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" x-text="message"></p>
            </div>
            <div class="flex justify-center space-x-3 mt-4">
                <button @click="cancel()" 
                        class="px-4 py-2 bg-gray-500 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition duration-300">
                    Annuler
                </button>
                <button @click="confirm()" 
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition duration-300">
                    Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete() {
        return {
            show: false,
            title: 'Confirmer la suppression',
            message: 'Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.',
            onConfirm: null,
            
            open(title, message, onConfirm) {
                this.title = title || 'Confirmer la suppression';
                this.message = message || 'Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.';
                this.onConfirm = onConfirm;
                this.show = true;
            },
            
            confirm() {
                if (this.onConfirm) {
                    this.onConfirm();
                }
                this.show = false;
            },
            
            cancel() {
                this.show = false;
            }
        }
    }
</script>