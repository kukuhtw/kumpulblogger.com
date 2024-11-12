<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            position: relative;
            min-height: 100vh;
        }

        /* Sidebar styles */
        .sidebar {
            background-color: #343a40;
            padding: 20px;
            height: 100vh;
            position: fixed;
            left: 0;
            transition: 0.3s;
            width: 250px;
            color: white;
        }

        .sidebar.hidden {
            left: -250px;
        }

        /* Container adjustment when sidebar is hidden */
        .container {
            margin-left: 250px;
            padding: 20px;
            transition: 0.3s;
        }

        .container.shifted {
            margin-left: 0;
        }

        .navbar {
            background-color: #343a40;
            color: white;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar ul li a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: white;
        }

        .sidebar ul li a:hover {
            background-color: #575757;
        }

        .card {
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #28a745;
            color: white;
            font-size: 24px;
            text-align: center;
        }

        .footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px;
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        /* Toggle Button */
        .toggle-btn {
            font-size: 20px;
            cursor: pointer;
            padding: 10px;
            background-color: #343a40;
            color: white;
            border: none;
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
        }

    </style>