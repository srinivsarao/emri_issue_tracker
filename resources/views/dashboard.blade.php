<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="font-semibold text-lg">{{ __('You are logged in as:') }} {{ auth()->user()->user_name ?? auth()->user()->login_id }}</p>
                    <p>{{ __('Role:') }} {{ auth()->user()->role_names ?: __('No role assigned') }}</p>

                    <div class="mt-4">
                        <h3 class="text-base font-semibold">{{ __('Mapped Pages') }}</h3>
                        @if(auth()->user()->privileges->isNotEmpty())
                            <ul class="list-disc list-inside mt-2">
                                @foreach(auth()->user()->privileges as $privilege)
                                    <li>{{ $privilege->module_name }} - {{ $privilege->privilege_name }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-2 text-sm text-gray-600">{{ __('No mapped pages or privileges found for your role.') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
