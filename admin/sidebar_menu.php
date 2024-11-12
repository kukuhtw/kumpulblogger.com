<!-- Toggle Button -->
<button class="toggle-btn" onclick="toggleSidebar()">☰</button>

<div class="sidebar" id="sidebar">
    <h4>Welcome, <br>
        <?php echo htmlspecialchars($loginemail_admin); ?></h4>
    <ul class="nav flex-column">
        <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>

        <!-- Expandable Manage Partner Menu -->
        <li>
            <a href="#partnerMenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-users"></i> Manage Partner</a>
            <ul class="collapse list-unstyled" id="partnerMenu">
                <li><a href="join_force.php"><i class="fas fa-handshake"></i> Join Force</a></li>
                <li><a href="manage_partner.php"><i class="fas fa-user-friends"></i> Manage Partner</a></li>
                <li><a href="manage_partner_request.php"><i class="fas fa-envelope"></i> Manage Partner Request</a></li>

                <li><a href="pay_provider_partner.php"><i class="fas fa-envelope"></i> pay_provider_partner</a></li>

                <li><a href="list_payment_provider_partner.php"><i class="fas fa-envelope"></i> list_payment_provider_partner</a></li>


                


            </ul>
        </li>

        
        <li><a href="entry_bank_account.php"><i class="fas fa-user"></i> Account Bank</a></li>


        <li><a href="manage_users.php"><i class="fas fa-user"></i> Manage Users</a></li>

        <!-- Expandable Section for Pubs Partner and Payment -->
        <li>
            <a href="#pubsPartnerMenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-list"></i> Pubs Partner & Payments</a>
            <ul class="collapse list-unstyled" id="pubsPartnerMenu">
                <li><a href="list_pubs_partner_revenue.php"><i class="fas fa-list"></i> List Pubs Partner Revenue</a></li>
                <li><a href="list_owner_pubs_partner_revenue.php"><i class="fas fa-list"></i> List Owner Pubs Partner Revenue</a></li>
                <li><a href="list_payment_pubs_partner.php"><i class="fas fa-list"></i> List Payment Pubs Partner</a></li>
                <li><a href="pay_pubs_partner.php"><i class="fas fa-money-bill-wave"></i> Pay Pubs Partner</a></li>
            </ul>
        </li>

        <li><a href="list_payment_pubs_local.php"><i class="fas fa-list"></i> List Payment Pubs Local</a></li>
        <li><a href="pay_pubs_local.php"><i class="fas fa-money-bill-wave"></i> Pay Pubs Local</a></li>
        
        <li><a href="manage_ads.php"><i class="fas fa-ad"></i> Manage Ads</a></li>

    <li><a href="manage_ads_partner.php"><i class="fas fa-ad"></i> Manage Ads Partners</a></li>


        <li><a href="manage_publishers.php"><i class="fas fa-bullhorn"></i> Manage Publisher</a></li>

        <!-- Expandable Settings Menu -->
        <li>
            <a href="#Settings" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-cog"></i> Settings</a>
            <ul class="collapse list-unstyled" id="Settings">
                <li><a href="change_code_provider.php"><i class="fas fa-code"></i> Change Provider Code</a></li>
                <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
            </ul>
        </li>


 <li><a href="list_setting_list_ip_banned.php"><i class="fas fa-sign-out-alt"></i> list_setting_list_ip_banned</a></li>

 

 <li><a href="list_setting_list_browser_banned.php"><i class="fas fa-sign-out-alt"></i> list_setting_list_browser_banned</a></li>



 <li><a href="list_setting_rule_clicks.php"><i class="fas fa-sign-out-alt"></i> list_setting_rule_clicks</a></li>

        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Include Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

<!-- Include Bootstrap JS and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
