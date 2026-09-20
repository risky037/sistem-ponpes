<div>
    <header class="top-header">
        <nav class="navbar navbar-expand">
            <div class="left-topbar d-flex align-items-center">
                <a href="javascript:;" class="toggle-btn"> <i class="bx bx-menu"></i>
                </a>
            </div>
            <div class="right-topbar ms-auto">
                <ul class="navbar-nav">

                    <li class="nav-item dropdown dropdown-user-profile">
                        <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="javascript:;"
                            data-bs-toggle="dropdown">
                            <div class="d-flex user-box align-items-center">
                                <div class="user-info">
                                    <p class="user-name mb-0">{{ ucfirst(auth()->user()->name) }}</p>
                                    <p class="designattion mb-0">Online</p>
                                </div>
                                <img src="{{ url('assets/images/avatars/avatar-1.png') }}" class="user-img" loading="lazy"
                                    alt="user avatar">
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="{{ route('profil.show', auth()->user()->id) }}"><i
                                    class="bx bx-user"></i><span>Profile</span>
                            </a>
                            <a class="dropdown-item" href="{{ route('setting.index') }}"><i
                                    class="bx bx-cog"></i><span>Settings</span>
                            </a>
                            <div class="dropdown-divider mb-0"></div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" method='post' style="cursor: pointer" class="dropdown-item">
                                    <i class="bx bx-power-off"></i><span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
</div>
