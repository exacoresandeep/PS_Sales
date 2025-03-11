@if(Auth::check() && Auth::user()->role_id == 2)
<div class="side-menu d-flex flex-wrap">
    <div class="logo">
      <a href="{{ route('sales.dashboard') }}"><img src="{{asset('images/logo.svg')}}"></a>
    </div>          
    <div class="menu-cover d-flex flex-wrap">
      <div class="d-flex align-self-start flex-wrap">
        <a href="{{ route('sales.dashboard') }}" class="dashboard w-100"><i class="fa fa-home" aria-hidden="true"></i> Dashboard</a>
        <ul class="w-100">
            <li>
                <a class="menu-title">
                    <i class="fa fa-cubes" aria-hidden="true"></i>
                    Activity Management <span class="icon-right"><i class="fa fa-solid fa-chevron-down"></i></span>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="{{ route('sales.activity.activity-type-index') }}">
                            <i class="fa fa-list" aria-hidden="true"></i>
                            Activity Type <span class="icon-right"><i class="fa fa-caret-right" aria-hidden="true"></i></span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('sales.activity.index') }}">
                            <i class="fa fa-plus-square" aria-hidden="true"></i>
                            Assign Activities <span class="icon-right"><i class="fa fa-caret-right" aria-hidden="true"></i></span>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a class="menu-title">
                    <i class="fa fa-road" aria-hidden="true"></i>
                    Route Management <span class="icon-right"><i class="fa fa-solid fa-chevron-down"></i></span>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="{{ route('sales.route.type.index') }}">
                            <i class="fa fa-road" aria-hidden="true"></i>
                             Routes <span class="icon-right"><i class="fa fa-caret-right" aria-hidden="true"></i></span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('sales.route.index') }}">
                            <i class="fa fa-plus-square" aria-hidden="true"></i>
                            Assigned Routes <span class="icon-right"><i class="fa fa-caret-right" aria-hidden="true"></i></span>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a class="menu-title"  href="{{ route('sales.target.index') }}">
                    <i class="fa fa-road" aria-hidden="true"></i>
                    Target Management <span class="icon-right"></span>
                </a>
                {{-- <ul class="submenu">
                    <li>
                        <a href="{{ route('admin.target.index') }}">
                            <i class="fa fa-road" aria-hidden="true"></i>
                            All Targets <span class="icon-right"><i class="fa fa-caret-right" aria-hidden="true"></i></span>
                        </a>
                    </li>
                    <li>
                        <a href="create-target">
                            <i class="fa fa-plus-square" aria-hidden="true"></i>
                            Create Target <span class="icon-right"><i class="fa fa-caret-right" aria-hidden="true"></i></span>
                        </a>
                    </li>
                </ul> --}}
            </li>
        </ul>
        
     
      </div>
      <div class="logout d-flex align-self-end flex-wrap w-100">
        {{-- <a href="logout"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a> --}}
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fa fa-sign-out" aria-hidden="true"></i> Logout
        </a>
        
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
      </div>
    </div>
</div>
  @endif
  @if(Auth::check() && Auth::user()->role_id == 3)
  <div class="side-menu d-flex flex-wrap">
    <div class="logo">
      <a href="{{ route('accounts.dashboard') }}"><img src="{{asset('images/logo.svg')}}"></a>
    </div>          
    <div class="menu-cover d-flex flex-wrap">
      <div class="d-flex align-self-start flex-wrap">
        <a href="{{ route('accounts.dashboard') }}" class="dashboard w-100"><i class="fa fa-home" aria-hidden="true"></i> Dashboard</a>
        <ul class="w-100">
            <li>
                <a class="menu-title" href="{{ route('accounts.orders.index') }}">
                    <i class="fa fa-cubes" aria-hidden="true"></i>
                    Order Request <span class="icon-right"></span>
                </a>
             
            </li>
          
        </ul>
        
     
      </div>
      <div class="logout d-flex align-self-end flex-wrap w-100">
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fa fa-sign-out" aria-hidden="true"></i> Logout
        </a>
        
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
      </div>
    </div>
</div>
  @endif