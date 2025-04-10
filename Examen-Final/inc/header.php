<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Management System (MySQLi Procedural)</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin=""/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">


  <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js" integrity="sha256-WBkoXOwTeyKclOHuWtc+i2uENFpDZ9YPdf5Hf+D7ewM=" crossorigin=""></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">

<div class="container mx-auto px-4 py-8">

<header class="mb-8">
          <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-4">Event Management System (MySQLi Procedural)</h1>


          <div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0 mb-6">
              <form action="" method="GET" class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4">
                  <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                  <div class="flex-grow">
                      <input type="text" name="search" placeholder="Buscar eventos..."
                             value="<?php echo htmlspecialchars($search_query); ?>"
                             class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-base">
                  </div>
                  <div class="w-full md:w-auto">
                      <select name="category" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-base">
                          <option value="0">Todas las Categorías</option>
                          <?php foreach ($categories as $category): ?>
                              <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                  <?php echo htmlspecialchars($category['name']); ?>
                              </option>
                          <?php endforeach; ?>
                      </select>
                  </div>
                  <div>
                      <button type="submit" class="w-full md:w-auto px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                          Buscar
                      </button>
                  </div>
              </form>


              <div class="flex space-x-2">
                  <?php
                      // Helper function to build query string for view links
                      function build_view_link_params($current_search, $current_category) {
                          $link_params = '';
                          if (!empty($current_search)) {
                              $link_params .= '&search='.urlencode($current_search);
                          }
                          if ($current_category > 0) {
                              $link_params .= '&category='.$current_category;
                          }
                          return $link_params;
                      }
                      $link_extra_params = build_view_link_params($search_query, $category_filter);
                  ?>
                  <a href="restaurar_BBDD.php"
                     class="px-4 py-2 rounded-lg <?php echo $view_mode == '' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                      Reinstalar BBDD
                  </a>
                  <a href="?view=list<?php echo $link_extra_params; ?>"
                     class="px-4 py-2 rounded-lg <?php echo $view_mode == 'list' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                      Lista
                  </a>
                  <a href="?view=grid<?php echo $link_extra_params; ?>"
                     class="px-4 py-2 rounded-lg <?php echo $view_mode == 'grid' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                      Cuadricula
                  </a>
                  <a href="?view=table<?php echo $link_extra_params; ?>"
                     class="px-4 py-2 rounded-lg <?php echo $view_mode == 'table' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                      Tabla
                  </a>
                  <a href="?view=map<?php echo $link_extra_params; ?>"
                     class="px-4 py-2 rounded-lg <?php echo $view_mode == 'map' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                      Mapa
                  </a>
                  <a href="?view=calendar<?php echo $link_extra_params; ?>"
                     class="px-4 py-2 rounded-lg <?php echo $view_mode == 'calendar' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                      Calendario
                  </a>
              </div>
          </div>
</header>
<main>