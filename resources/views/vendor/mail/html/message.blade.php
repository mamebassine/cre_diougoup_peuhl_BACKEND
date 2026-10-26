<x-mail::layout>
    {{-- En-tête du CRE --}}
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            Centre de recherche et d’essai de Diougoup Peuhl
        </x-mail::header>
    </x-slot:header>

    {{-- Corps du message --}}
    {!! $slot !!}

    {{-- Informations complémentaires --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {!! $subcopy !!}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Pied de page personnalisé --}}
    <x-slot:footer>
        <x-mail::footer>
            © {{ date('Y') }} — Centre de recherche et d’essai de Diougoup Peuhl.
            Tous droits réservés.
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
