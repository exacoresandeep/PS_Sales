<div class="">
    <div class="side-menu d-flex flex-wrap">
        <div class="logo">
            <img src="{{ asset('images/logo.svg') }}">
        </div>          
        <div class="menu-cover d-flex flex-wrap">
            <div class="d-flex align-self-start flex-wrap">
                <a href="{{ route('admin.dashboard') }}" class="dashboard w-100">
                    <i class="fa fa-home" aria-hidden="true"></i> Dashboard
                </a>
                <ul class="w-100 side-menu-links">
                    <li class="has-submenu">
                        <a href="#">
                            <i class="fa fa-cubes" aria-hidden="true"></i> Activity Management 
                            <span class="arrow">↓</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="activities"><i class="fa fa-list"></i> All Activities</a></li>
                        </ul>
                    </li>
                    <li class="has-submenu">
                        <a href="#">
                            <i class="fa fa-road" aria-hidden="true"></i> Route Management 
                            <span class="arrow">↓</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="routes"><i class="fa fa-road"></i> All Route</a></li>
                        </ul>
                    </li>
                    <li class="has-submenu">
                        <a href="#">
                            <i class="fa fa-road" aria-hidden="true"></i> Product Price Management 
                            <span class="arrow">↓</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="product-price"><i class="fa fa-road"></i> All Product Price</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="logout d-flex justify-content-center align-items-center flex-wrap w-100">
                {{-- <a href=""><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a> --}}
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-link">
                        <i class="fa fa-sign-out"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

