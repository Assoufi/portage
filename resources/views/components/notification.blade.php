{{-- resources/views/components/notification.blade.php --}}
<div x-data="notification()" 
     x-show="show" 
     x-transition.duration.300ms
     class="fixed top-4 right-4 z-50 max-w-sm w-full"
     style="display: none;">
    <div class="rounded-lg shadow-lg overflow-hidden"
         :class="{
             'bg-green-50 border-l-4 border-green-400': type === 'success',
             'bg-red-50 border-l-4 border-red-400': type === 'error',
             'bg-yellow-50 border-l-4 border-yellow-400': type === 'warning',
             'bg-blue-50 border-l-4 border-blue-400': type === 'info'
         }">
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg x-show="type === 'success'" class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <svg x-show="type === 'error'" class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <svg x-show="type === 'warning'" class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <svg x-show="type === 'info'" class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-sm font-medium" :class="{
                        'text-green-800': type === 'success',
                        'text-red-800': type === 'error',
                        'text-yellow-800': type === 'warning',
                        'text-blue-800': type === 'info'
                    }" x-text="message"></p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button @click="show = false" class="inline-flex rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2" :class="{
                        'text-green-500 hover:text-green-700': type === 'success',
                        'text-red-500 hover:text-red-700': type === 'error',
                        'text-yellow-500 hover:text-yellow-700': type === 'warning',
                        'text-blue-500 hover:text-blue-700': type === 'info'
                    }">
                        <span class="sr-only">Fermer</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function notification() {
        return {
            show: false,
            type: 'info',
            message: '',
            timeout: null,
            
            showNotification(type, message, duration = 3000) {
                this.type = type;
                this.message = message;
                this.show = true;
                
                if (this.timeout) clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    this.show = false;
                }, duration);
            }
        }
    }
</script>