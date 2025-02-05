<div class="side-menu d-flex flex-wrap">
    <div class="logo">
      <a href="index.php"><img src="{{asset('images/logo.svg')}}"></a>
    </div>          
    <div class="menu-cover d-flex flex-wrap">
      <div class="d-flex align-self-start flex-wrap">
        <a href="index.php" class="dashboard w-100"><i class="fa fa-home" aria-hidden="true"></i> Dashboard</a>
        <ul class="w-100">              
          <li>
            <a class="menu-title">
              <i class="fa fa-cubes" aria-hidden="true"></i>
              Activity Management
            </a>
            <ul>
              <li>
                <a href="activity-management.php">
                  <i class="fa fa-list" aria-hidden="true"></i>
                  All Activities
                </a>
              </li>
              <li>
                <a href="create-activity.php">
                  <i class="fa fa-plus-square" aria-hidden="true"></i>
                  Create Activities
                </a>
              </li>
            </ul>
          </li>
          <li>
            <a class="menu-title">
              <i class="fa fa-road" aria-hidden="true"></i>
              Route Management
            </a>
            <ul>
              <li>
                <a href="route-management.php">
                  <i class="fa fa-road" aria-hidden="true"></i>
                  All Route
                </a>
              </li>
              <li>
                <a href="create-route.php">
                  <i class="fa fa-plus-square" aria-hidden="true"></i>
                  Create Route
                </a>
              </li>
            </ul>
          </li>
          <li>
            <a class="menu-title">
              <i class="fa fa-road" aria-hidden="true"></i>
              Target Management
            </a>
            <ul>
              <li>
                <a href="target-management.php">
                  <i class="fa fa-road" aria-hidden="true"></i>
                  All Targets
                </a>
              </li>
              <li>
                <a href="create-target.php">
                  <i class="fa fa-plus-square" aria-hidden="true"></i>
                  Create Target
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </div>
      <div class="logout d-flex align-self-end flex-wrap w-100">
        <a href="login.php"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a>
      </div>
    </div>
  </div>