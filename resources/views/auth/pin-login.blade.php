@extends('layouts.guest')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('pin-login') }}" id="pin-form">
        @csrf

        <input type="hidden" name="pin" id="pin-value">

        <div class="d-flex justify-content-center gap-2 mb-4" id="pin-dots">
            @for ($i = 0; $i < 8; $i++)
                <span class="pin-dot border rounded-circle" data-index="{{ $i }}"
                      style="width: 28px; height: 28px; display: inline-block;"></span>
            @endfor
        </div>

        <div class="row row-cols-3 g-2 mx-auto" style="max-width: 280px;">
            @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $digit)
                <div class="col">
                    <button type="button" class="btn btn-outline-secondary w-100 py-3 pin-key" data-digit="{{ $digit }}">
                        {{ $digit }}
                    </button>
                </div>
            @endforeach
            <div class="col">
                <button type="button" class="btn btn-outline-danger w-100 py-3" id="pin-clear">C</button>
            </div>
            <div class="col">
                <button type="button" class="btn btn-outline-secondary w-100 py-3 pin-key" data-digit="0">0</button>
            </div>
            <div class="col">
                <button type="button" class="btn btn-outline-warning w-100 py-3" id="pin-backspace">&larr;</button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 mt-4" id="pin-submit" disabled>
            Se connecter
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}">Se connecter avec e-mail et mot de passe</a>
    </div>

    <style>
        .pin-dot { background-color: #f1ede7; }
        .pin-dot.filled { background-color: #8a6d3b; border-color: #8a6d3b !important; }
    </style>

    <script>
        (function () {
            const maxLength = 8;
            let pin = '';
            const hiddenInput = document.getElementById('pin-value');
            const dots = document.querySelectorAll('.pin-dot');
            const submitButton = document.getElementById('pin-submit');
            const form = document.getElementById('pin-form');

            function render() {
                hiddenInput.value = pin;
                dots.forEach((dot, index) => {
                    dot.classList.toggle('filled', index < pin.length);
                });
                submitButton.disabled = pin.length !== maxLength;
            }

            document.querySelectorAll('.pin-key').forEach((button) => {
                button.addEventListener('click', () => {
                    if (pin.length < maxLength) {
                        pin += button.dataset.digit;
                        render();
                        if (pin.length === maxLength) {
                            form.submit();
                        }
                    }
                });
            });

            document.getElementById('pin-backspace').addEventListener('click', () => {
                pin = pin.slice(0, -1);
                render();
            });

            document.getElementById('pin-clear').addEventListener('click', () => {
                pin = '';
                render();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key >= '0' && event.key <= '9' && pin.length < maxLength) {
                    pin += event.key;
                    render();
                    if (pin.length === maxLength) {
                        form.submit();
                    }
                } else if (event.key === 'Backspace') {
                    pin = pin.slice(0, -1);
                    render();
                }
            });

            render();
        })();
    </script>
@endsection
