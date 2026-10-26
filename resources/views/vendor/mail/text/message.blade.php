{{-- Mise en page des e-mails du CRE de Diougoup Peuhl --}}

<x-mail::layout>
    {{-- En-tête personnalisé --}}
    <x-slot:header>
        <a href="{{ config('app.url') }}" style="display: inline-block; text-decoration: none;">
            <img
                src="{{ asset('images/logocre.png') }}"
                alt="Logo du CRE de Diougoup Peuhl"
                style="max-width: 150px; max-height: 100px; display: block; margin: 0 auto;"
            >
        </a>
        <div style="color: #3B5998; font-size: 20px; font-weight: bold; text-align: center; margin-top: 10px;">
            CRE DE DIOUGOUP PEUHL
        </div>
        <div style="color: #2E7D32; font-size: 13px; text-align: center; margin-top: 5px;">
            Centre de recherche et d’essai
        </div>
    </x-slot:header>

    {{-- Contenu de l'e-mail --}}
    {!! $slot !!}

    {{-- Informations complémentaires --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {!! $subcopy !!}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Pied de page en français --}}
    <x-slot:footer>
        <x-mail::footer>
            <div style="color: #000000; text-align: center;">
                © {{ date('Y') }} — CRE de Diougoup Peuhl
                <br>
                <span style="color: #3B5998;">Votre formation, notre engagement.</span>
            </div>
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>