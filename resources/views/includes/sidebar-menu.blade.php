@if(Auth::check() && Auth::user()->role_id == 2)
<div class="side-menu d-flex flex-wrap">
    <div class="logo">
      <a href="{{ route('sales.dashboard') }}"><img src="{{asset('images/logo.svg')}}"></a>
    </div>          
    <div class="menu-cover d-flex flex-wrap">
      <div class="d-flex align-self-start flex-wrap">
        <ul class="w-100">
            <li>
                <a class="menu-title"  href="{{ route('sales.dashboard') }}">
                    <i class="fa fa-home" aria-hidden="true"></i>
                    Dashboard <span class="icon-right"></span>
                </a>
                
            </li>
            <li>
                <a class="menu-title">
                    <i class="fa fa-tasks" aria-hidden="true"></i>
                    Activity Management <span class="icon-right"><i class="fa fa-solid fa-angle-down"></i></span>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="{{ route('sales.activity.activity-type-index') }}">Activity Type <span class="icon-right"></span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('sales.activity.index') }}">Assign Activities <span class="icon-right"></span>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a class="menu-title">
                    <i class="fa fa-road" aria-hidden="true"></i>
                    Route Management <span class="icon-right"><i class="fa fa-solid fa-angle-down"></i></span>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="{{ route('sales.route.type.index') }}">Routes <span class="icon-right"></span></a>
                    </li>
                    <li>
                        <a href="{{ route('sales.route.index') }}">Assigned Routes <span class="icon-right"></span></a>
                    </li>
                </ul>
            </li>
            <li>
                <a class="menu-title"  href="{{ route('sales.target.index') }}">
                    <i class="fa fa-bullseye" aria-hidden="true"></i>
                    Target Management <span class="icon-right"></span>
                </a>
                
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
      <div class="d-flex align-self-start flex-wrap w-100">
        <ul class="w-100">
            <li>
                <a class="menu-title"  href="{{ route('accounts.dashboard') }}">
                    <i class="fa fa-home" aria-hidden="true"></i>
                    Dashboard <span class="icon-right"></span>
                </a>
            </li>
            <li>
                <a class="menu-title" href="{{ route('accounts.orders.index') }}">
                    <i class="fa fa-shopping-cart" aria-hidden="true"></i>
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