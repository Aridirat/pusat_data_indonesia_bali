@php

$menus = [

    (object)[
        "title" => "Data",
        "path" => "data",
        "icon" => "fas fa-database",
        "active" => request()->segment(1) === 'data',
        "children" => []
    ],

    (object)[
        "title" => "Metadata",
        "path" => "/metadata",
        "icon" => "fas fa-folder-open",
        "active" => request()->is('metadata*'),
        "children" => [
            (object)[
                "title" => "All Metadata",
                "path" => "/metadata",
                "icon" => "fa-solid fa-floppy-disk",
                "active" => request()->is('metadata')
            ],

            (object)[
                "title" => "Approval",
                "path" => "/metadata/approval",
                "icon" => "fa-solid fa-clipboard-check",
                "active" => request()->is('metadata/approval')
            ]
        ]
    ],

    (object)[
        "title" => "Dimensi",
        "path" => "/dimensi",
        "icon" => "fas fa-cubes",
        "active" => request()->is('dimensi_*'),
        "children" => [
            (object)[
                "title" => "Dimensi Waktu",
                "path" => "/dimensi_waktu",
                "icon" => "fa-solid fa-clock",
                "active" => request()->segment(1) === 'dimensi_waktu'
            ],

            (object)[
                "title" => "Dimensi Lokasi",
                "path" => "/dimensi_lokasi",
                "icon" => "fa-solid fa-location-dot",
                "active" => request()->segment(1) === 'dimensi_lokasi'
            ]
        ]
    ],

    // (object)[
    //     "title" => "Kelola Pengguna",
    //     "path" => "#",
    //     "icon" => "fas fa-users",
    //     "active" => in_array(request()->segment(1), ['group','pengguna']),
    //     "children" => [

    //         (object)[
    //             "title" => "Group",
    //             "path" => "group",
    //             "icon" => "fas fa-users-cog",
    //             "active" => request()->segment(1) === 'group'
    //         ],

    //         (object)[
    //             "title" => "Pengguna",
    //             "path" => "pengguna",
    //             "icon" => "fas fa-user",
    //             "active" => request()->segment(1) === 'pengguna'
    //         ]
    //     ]
    // ],

];

@endphp

<!-- Sidebar -->
    <aside class="w-54 h-full bg-stikom shadow-lg flex flex-col justify-between rounded-sm">

        <div>
            <!-- Logo -->
            <div class="px-6 py-3  flex items-center gap-3 border-b border-gray-600">
                <div class="w-8 h-8 bg-stikom_accent rounded-full flex items-center justify-center text-white font-bold">
                    PDIB
                </div>
                <div>
                    <h1 class="font-bold text-sm text-gray-200">PUSAT DATA</h1>
                    <h1 class="font-bold text-sm text-gray-200">INDONESIA BALI</h1>
                </div>
            </div>

            <!-- Menu -->
            <nav class="text-sm font-medium py-2 p-4">
                <ul class="flex flex-col gap-1">
                    @foreach ($menus as $menu)

                        <li class="relative group">

                            <!-- Parent Menu -->
                            <a href="{{ $menu->children ? '#' : url($menu->path) }}"
                            class="flex items-center justify-between text-xs font-normal py-2 rounded-sm transition
                            {{ $menu->active 
                                    ? 'text-stikom_accent font-semibold' 
                                    : 'text-gray-400 hover:text-white'
                            }}">

                                <div class="flex items-center gap-3">
                                    <i class="{{ $menu->icon }}"></i>
                                    <span>{{ $menu->title }}</span>
                                </div>

                                @if($menu->children)
                                    <i class="fas fa-chevron-down text-xs"></i>
                                @endif
                            </a>

                            <!-- Submenu -->
                            @if ($menu->children)

                                <ul class="hidden group-hover:block ml-2 mt-1 space-y-1 rounded-sm border-l-4 border-amber-300">

                                    @foreach ($menu->children as $child)

                                        <li>
                                            <a href="{{ url($child->path) }}"
                                            class="block rounded-e-lg px-3 py-1 transition text-xs font-normal
                                            {{ $child->active
                                                    ? 'text-stikom_accent font-semibold'
                                                    : 'text-gray-400 hover:text-white'
                                            }}">
                                                <div class="flex items-center gap-3 py-1">
                                                    <i class="{{ $child->icon }}"></i>
                                                    <span>{{ $child->title }}</span>
                                                </div>
                                            </a>
                                        </li>

                                    @endforeach

                                </ul>

                            @endif

                        </li>

                    @endforeach
                </ul>
            </nav>

        </div>

        <!-- Logout -->
        <div class="p-4 border-t border-gray-600 justify-items-center">
            <form action="/logout" method="POST">
                @csrf
                <button 
                    type="submit" 
                    class="w-36 font-bold text-sm bg-linear-to-r from-red-600 to-rose-500 hover:from-red-800 hover:to-rose-800 shadow-md shadow-red-500/40 text-white py-2 rounded transition duration-300 ease-in-out hover:translate-y-0.5 hover:scale-99">
                    <i class="fa-solid fa-arrow-right-from-bracket rotate-180"></i> Logout
                </button>
            </form>
        </div>

    </aside>