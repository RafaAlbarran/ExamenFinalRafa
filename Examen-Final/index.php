<?php require "inc/config.php"?>

<?php
  
  //conectar con BBDD
  $conn=conexionBD();

  // Establecer el conjunto de caracteres en UTF-8
  if (!mysqli_set_charset($conn, "utf8mb4")) {
      printf("Error loading character set utf8mb4: %s\n", mysqli_error($conn));
      // Considere morir aquí si el conjunto de caracteres es crítico
  }

  // Obtener el modo de vista actual (lista, cuadrícula, tabla, mapa, calendario)
  $view_mode = isset($_GET['view']) ? $_GET['view'] : 'list';


  // Obtener la consulta de búsqueda si está presente
  $search_query = isset($_GET['search']) ? trim($_GET['search']) : ''; // Trim whitespace


  // Obtener filtro de categoría si está presente
  $category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;


  // Obtener el ID del evento para la vista detallada
  $event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

  // Obtener categorías para filtrar usando mysqli_query
  $categories = [];
  $sql_categories = "SELECT id, name FROM categories ORDER BY name";
  $result_categories = mysqli_query($conn, $sql_categories);


  if ($result_categories) {
      $categories = mysqli_fetch_all($result_categories, MYSQLI_ASSOC);
      mysqli_free_result($result_categories); // Conjunto de resultados gratuito
  } else {
      echo "Error fetching categories: " . mysqli_error($conn);
      // Decide si quieres morir() aquí o continuar sin categorías
  }

  // Consulta base para eventos
  $query = "SELECT e.*, c.name as category_name
            FROM events e
            LEFT JOIN categories c ON e.category_id = c.id
            WHERE 1";
  $params = [];
  $types = ""; // Cadena para tipos de parámetros (i=entero, s=cadena, d=doble, b=blob)


  // Agregar condición de búsqueda si se proporciona una consulta de búsqueda
  if (!empty($search_query)) {
      $query .= " AND (e.title LIKE ? OR e.description LIKE ?)";
      $search_param = "%" . $search_query . "%";
      $params[] = $search_param; // Agregar parámetro para el título
      $params[] = $search_param; // Añadir parámetro para la descripción
      $types .= "ss"; // Dos parámetros de cadena
  }


  // Agregar filtro de categoría si está seleccionado
  if ($category_filter > 0) {
      $query .= " AND e.category_id = ?";
      $params[] = $category_filter; // Agregar parámetro de ID de categoría
      $types .= "i"; // Un parámetro entero
  }


  // Añadir orden por fecha
  $query .= " ORDER BY e.event_date";


  // Preparar y ejecutar consultas para eventos utilizando sentencias preparadas de MySQL
  $events = [];
  $stmt = mysqli_prepare($conn, $query);


  if ($stmt) {
      // Vincular parámetros si existen
      if (!empty($params)) {
          // Utilice el operador splat (...) para pasar elementos de la matriz como argumentos individuales
          if (!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
              echo "Error binding parameters: " . mysqli_stmt_error($stmt);
              die();
          }
      }

      // Ejecutar la sentencia
      if (mysqli_stmt_execute($stmt)) {
          // Obtenga el resultado.t
          $result_events = mysqli_stmt_get_result($stmt);
          if ($result_events) {
              // Obtener todos los resultados
              $events = mysqli_fetch_all($result_events, MYSQLI_ASSOC);
              // El objeto de resultado se libera implícitamente cuando se cierra la instrucción
          } else {
              echo "Error getting result set: " . mysqli_stmt_error($stmt);
          }
      } else {
          echo "Error executing statement: " . mysqli_stmt_error($stmt);
      }

      // Cerrar la declaración
      mysqli_stmt_close($stmt);
  } else {
      echo "Error preparing statement: " . mysqli_error($conn);
      die(); // Error crítico si no se puede preparar la declaración
  }
?>

<?php include "inc/header.php"?>
   

          <?php if ($event_id > 0): ?>
              <?php
              // Obtener detalles de un solo evento usando sentencias preparadas de MySQL
              $event = null; // Inicializar evento
              $sql_single = "SELECT e.*, c.name as category_name
                             FROM events e
                             LEFT JOIN categories c ON e.category_id = c.id
                             WHERE e.id = ?";
              $stmt_single = mysqli_prepare($conn, $sql_single);


              if ($stmt_single) {
                  // Vincular el parámetro entero
                  mysqli_stmt_bind_param($stmt_single, 'i', $event_id);


                  // Ejecutar
                  if (mysqli_stmt_execute($stmt_single)) {
                      // Obtener resultado
                      $result_single = mysqli_stmt_get_result($stmt_single);
                      if ($result_single) {
                          // Obtener la fila única
                          $event = mysqli_fetch_assoc($result_single);
                      } else {
                          echo "Error getting result for single event: " . mysqli_stmt_error($stmt_single);
                      }
                  } else {
                      echo "Error executing single event query: " . mysqli_stmt_error($stmt_single);
                  }
                  // Cerrar declaración
                  mysqli_stmt_close($stmt_single);
              } else {
                  echo "Error preparing single event statement: " . mysqli_error($conn);
              }


              if ($event): // Comprobar si el evento se obtuvo correctamente
              ?>
                  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                      <div class="p-6">
                          <div class="flex justify-between items-start">
                              <div>
                                  <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2"><?php echo htmlspecialchars($event['title']); ?></h2>
                                  <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                      <span class="inline-block bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 rounded-full px-3 py-1 text-sm font-semibold mr-2">
                                          <?php echo htmlspecialchars($event['category_name'] ?? 'Uncategorized'); ?>
                                      </span>
                                      <span>
                                          <?php echo date('F j, Y - g:i A', strtotime($event['event_date'])); ?>
                                      </span>
                                  </p>
                              </div>
                              <a href="?view=<?php echo $view_mode; ?><?php echo $link_extra_params; ?>"
                                 class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                  Back to Events
                              </a>
                          </div>


                          <?php if (!empty($event['image_url'])): ?>
                              <div class="mb-6">
                                  <img src="img/<?php echo htmlspecialchars($event['image_url']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="w-full h-64 object-cover rounded-lg">
                              </div>
                          <?php endif; ?>


                          <div class="mb-6">
                              <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Description</h3>
                              <p class="text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                          </div>


                          <div class="mb-6">
                              <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Location</h3>
                              <p class="text-gray-700 dark:text-gray-300 mb-4"><?php echo htmlspecialchars($event['location']); ?></p>


                              <?php if (!empty($event['latitude']) && !empty($event['longitude'])): ?>
                                  <div id="detailMap" class="h-64 rounded-lg"></div>
                                  <script>
                                      document.addEventListener('DOMContentLoaded', function() {
                                          // Asegúrese de que el folleto esté cargado antes de intentar usarlo
                                          if (typeof L !== 'undefined') {
                                              const map = L.map('detailMap').setView([<?php echo $event['latitude']; ?>, <?php echo $event['longitude']; ?>], 15);


                                              L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                                  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                              }).addTo(map);


                                              L.marker([<?php echo $event['latitude']; ?>, <?php echo $event['longitude']; ?>])
                                                  .addTo(map)
                                                  .bindPopup("<?php echo htmlspecialchars(addslashes($event['title'])); // Utilice barras adicionales para cadenas JS en ventanas emergentes ?>")
                                                  .openPopup();
                                          } else {
                                              console.error("Leaflet library not loaded.");
                                          }
                                      });
                                  </script>
                              <?php endif; ?>
                          </div>
                      </div>
                  </div>
              <?php else: ?>
                  <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                      Event not found or there was an error retrieving event details.
                  </div>
              <?php endif; ?>


          <?php else: // Mostrar la vista de lista/cuadrícula/tabla/mapa/calendario ?>


              <?php if ($view_mode == 'list'): ?>
                  <div class="space-y-6">
                      <?php if (empty($events)): ?>
                          <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                              No events found matching your criteria.
                          </div>
                      <?php endif; ?>


                      <?php foreach ($events as $event): ?>
                          <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow duration-300 overflow-hidden flex flex-col md:flex-row">
                              <?php if (!empty($event['image_url'])): ?>
                                  <div class="md:w-1/4 h-48 md:h-auto">
                                      <img src="img/<?php echo htmlspecialchars($event['image_url']); ?>"
                                           alt="<?php echo htmlspecialchars($event['title']); ?>"
                                           class="w-full h-full object-cover">
                                  </div>
                              <?php endif; ?>
                              <div class="p-6 flex-1">
                                  <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                      <a href="?view=<?php echo $view_mode; ?>&event_id=<?php echo $event['id']; ?><?php echo $link_extra_params; ?>"
                                         class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                          <?php echo htmlspecialchars($event['title']); ?>
                                      </a>
                                  </h2>
                                  <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                      <span class="inline-block bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 rounded-full px-3 py-1 text-sm font-semibold mr-2">
                                          <?php echo htmlspecialchars($event['category_name'] ?? 'Uncategorized'); ?>
                                      </span>
                                      <span>
                                          <?php echo date('F j, Y - g:i A', strtotime($event['event_date'])); ?>
                                      </span>
                                  </p>
                                  <p class="text-gray-700 dark:text-gray-300 mb-4">
                                      <?php echo substr(htmlspecialchars($event['description']), 0, 150) . (strlen($event['description']) > 150 ? '...' : ''); ?>
                                  </p>
                                  <div class="flex justify-between items-center">
                                      <div class="text-gray-600 dark:text-gray-400">
                                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1113.314-13.314 8 8 0 010 11.314z" />
                                          </svg>
                                          <?php echo htmlspecialchars($event['location']); ?>
                                      </div>
                                      <a href="?view=<?php echo $view_mode; ?>&event_id=<?php echo $event['id']; ?><?php echo $link_extra_params; ?>"
                                         class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">
                                          Ver Detalles
                                      </a>
                                  </div>
                              </div>
                          </div>
                      <?php endforeach; ?>
                  </div>


              <?php elseif ($view_mode == 'grid'): ?>
                  <?php if (empty($events)): ?>
                      <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                          No events found matching your criteria.
                      </div>
                  <?php else: ?>
                      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                          <?php foreach ($events as $event): ?>
                              <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow duration-300 overflow-hidden flex flex-col h-full">
                                  <?php if (!empty($event['image_url'])): ?>
                                      <div class="h-48 overflow-hidden">
                                          <img src="img/<?php echo htmlspecialchars($event['image_url']); ?>"
                                               alt="<?php echo htmlspecialchars($event['title']); ?>"
                                               class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                                      </div>
                                  <?php endif; ?>
                                  <div class="p-6 flex-1 flex flex-col">
                                      <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                          <a href="?view=<?php echo $view_mode; ?>&event_id=<?php echo $event['id']; ?><?php echo $link_extra_params; ?>"
                                             class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                              <?php echo htmlspecialchars($event['title']); ?>
                                          </a>
                                      </h2>
                                      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                          <span class="inline-block bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 rounded-full px-3 py-1 text-sm font-semibold mr-2">
                                              <?php echo htmlspecialchars($event['category_name'] ?? 'Uncategorized'); ?>
                                          </span>
                                          <span>
                                              <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
                                          </span>
                                      </p>
                                      <p class="text-gray-700 dark:text-gray-300 mb-4 flex-1">
                                          <?php echo substr(htmlspecialchars($event['description']), 0, 100) . (strlen($event['description']) > 100 ? '...' : ''); ?>
                                      </p>
                                      <div class="flex justify-between items-center mt-auto">
                                          <div class="text-gray-600 dark:text-gray-400 text-sm truncate mr-2">
                                              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1113.314-13.314 8 8 0 010 11.314z" />
                                              </svg>
                                              <?php echo htmlspecialchars($event['location']); ?>
                                          </div>
                                          <a href="?view=<?php echo $view_mode; ?>&event_id=<?php echo $event['id']; ?><?php echo $link_extra_params; ?>"
                                             class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">
                                              Vista
                                          </a>
                                      </div>
                                  </div>
                              </div>
                          <?php endforeach; ?>
                      </div>
                  <?php endif; ?>


              <?php elseif ($view_mode == 'table'): ?>
                  <?php if (empty($events)): ?>
                      <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                          No events found matching your criteria.
                      </div>
                  <?php else: ?>
                      <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
                          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                              <thead class="bg-gray-50 dark:bg-gray-700">
                                  <tr>
                                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                          Título
                                      </th>
                                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                          Categoría
                                      </th>
                                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                          Fecha & Hora
                                      </th>
                                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                          Localización
                                      </th>
                                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                          Acción
                                      </th>
                                  </tr>
                              </thead>
                              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                  <?php foreach ($events as $event): ?>
                                      <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                          <td class="px-6 py-4 whitespace-nowrap">
                                              <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                  <?php echo htmlspecialchars($event['title']); ?>
                                              </div>
                                          </td>
                                          <td class="px-6 py-4 whitespace-nowrap">
                                              <span class="inline-block bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 rounded-full px-3 py-1 text-xs font-semibold">
                                                  <?php echo htmlspecialchars($event['category_name'] ?? 'Uncategorized'); ?>
                                              </span>
                                          </td>
                                          <td class="px-6 py-4 whitespace-nowrap">
                                              <div class="text-sm text-gray-700 dark:text-gray-300">
                                                  <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                                              </div>
                                              <div class="text-sm text-gray-500 dark:text-gray-400">
                                                  <?php echo date('g:i A', strtotime($event['event_date'])); ?>
                                              </div>
                                          </td>
                                          <td class="px-6 py-4 whitespace-nowrap">
                                              <div class="text-sm text-gray-700 dark:text-gray-300">
                                                  <?php echo htmlspecialchars($event['location']); ?>
                                              </div>
                                          </td>
                                          <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                              <a href="?view=<?php echo $view_mode; ?>&event_id=<?php echo $event['id']; ?><?php echo $link_extra_params; ?>"
                                                 class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                  Ver Detalles
                                              </a>
                                          </td>
                                      </tr>
                                  <?php endforeach; ?>
                              </tbody>
                          </table>
                      </div>
                  <?php endif; ?>


              <?php elseif ($view_mode == 'map'): ?>
                  <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                      <div id="mapView" class="h-96 w-full rounded-lg"></div>


                      <script>
                          document.addEventListener('DOMContentLoaded', function() {
                              // Asegúrese de que el folleto esté cargado
                              if (typeof L !== 'undefined') {
                                  const map = L.map('mapView').setView([20, 0], 2); // Default view


                                  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                  }).addTo(map);


                                  // Obtener datos de eventos PHP en JS: use json_encode con cuidado
                                  // Asegúrese de que los datos pasados ​​a json_encode no contengan caracteres UTF-8 no válidos
                                  // Utilice JSON_INVALID_UTF8_IGNORE si es necesario en PHP >= 7.2
                                  const events = <?php echo json_encode($events, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE); ?>;
                                  const markers = [];
                                  const bounds = [];


                                  if (Array.isArray(events)) {
                                      events.forEach(event => {
                                          // Verifique que la latitud y longitud sean válidas Y asegúrese de que sean números
                                          const lat = parseFloat(event.latitude);
                                          const lon = parseFloat(event.longitude);


                                          if (!isNaN(lat) && !isNaN(lon)) {
                                               // Desinfectar el contenido para la ventana emergente
                                               const title = event.title ? event.title.replace(/'/g, "\\'").replace(/"/g, '\\"') : 'Untitled Event';
                                               const dateStr = event.event_date ? new Date(event.event_date).toLocaleDateString() : 'N/A';
                                               const timeStr = event.event_date ? new Date(event.event_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : 'N/A';
                                               const eventId = event.id;
                                               const detailsLink = `?view=<?php echo $view_mode; ?>&event_id=${eventId}<?php echo $link_extra_params; ?>`;


                                              const marker = L.marker([lat, lon])
                                                  .addTo(map)
                                                  .bindPopup(`
                                                      <div class="text-center p-1">
                                                          <h3 class="font-bold text-base mb-1">${title}</h3>
                                                          <p class="text-xs mb-2">${dateStr} - ${timeStr}</p>
                                                          <a href="${detailsLink}"
                                                             class="text-indigo-600 hover:underline text-xs font-medium">
                                                              Ver Detalles
                                                          </a>
                                                      </div>
                                                  `);
                                              markers.push(marker);
                                              bounds.push([lat, lon]);
                                          }
                                      });
                                  } else {
                                      console.error("Events data is not an array:", events);
                                  }




                                  if (bounds.length > 0) {
                                      map.fitBounds(bounds, { padding: [50, 50] }); // Añade algo de relleno
                                  } else {
                                      //Opcional: Establezca una vista predeterminada si no hay marcadores
                                      map.setView([40, -3], 5); // Ejemplo: Centrarse en España si no hay eventos
                                  }
                              } else {
                                  console.error("Leaflet library not loaded.");
                              }
                          });
                      </script>


                      <?php if (empty($events) || !array_filter($events, function($e) { return !empty($e['latitude']) && !empty($e['longitude']) && is_numeric($e['latitude']) && is_numeric($e['longitude']); })): ?>
                          <div class="mt-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                              <?php if (empty($events)): ?>
                                   No events found matching your criteria.
                              <?php else: ?>
                                   No events with valid location data found matching your criteria.
                              <?php endif; ?>
                          </div>
                      <?php endif; ?>
                  </div>


              <?php elseif ($view_mode == 'calendar'): ?>
                  <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                      <div id="calendarView" class="h-auto"></div>


                      <script>
                          document.addEventListener('DOMContentLoaded', function() {
                              // Asegúrese de que FullCalendar esté cargado
                              if (typeof FullCalendar !== 'undefined') {
                                  const calendarEl = document.getElementById('calendarView');
                                  if (calendarEl) {
                                      const calendar = new FullCalendar.Calendar(calendarEl, {
                                          initialView: 'dayGridMonth',
                                          headerToolbar: {
                                              left: 'prev,next today',
                                              center: 'title',
                                              right: 'dayGridMonth,timeGridWeek,listWeek'
                                          },
                                          events: [
                                              <?php foreach ($events as $event): ?>
                                              {
                                                  id: '<?php echo $event['id']; ?>',
                                                  // Utilice addslashes para cadenas JS desde PHP
                                                  title: '<?php echo addslashes(htmlspecialchars($event['title'])); ?>',
                                                  start: '<?php echo $event['event_date']; // Suponiendo el formato Y-m-d H:i:s ?>',
                                                  url: '?view=<?php echo $view_mode; ?>&event_id=<?php echo $event['id']; ?><?php echo $link_extra_params; ?>',
                                                  extendedProps: {
                                                      category: '<?php echo addslashes(htmlspecialchars($event['category_name'] ?? 'Uncategorized')); ?>'
                                                  },
                                                  // Opcional: agregue una clase según el ID de categoría si es necesario para el estilo
                                                  classNames: ['event-category-<?php echo $event['category_id'] ?? 0; ?>']
                                              },
                                              <?php endforeach; ?>
                                          ],
                                          eventClick: function(info) {
                                              info.jsEvent.preventDefault(); // Impedir la navegación del navegador
                                              if (info.event.url) {
                                                  window.location.href = info.event.url; // Ir a la página de detalles
                                              }
                                          },
                                          // Manejar posibles errores de carga de eventos
                                          loading: function(isLoading) {
                                               if (isLoading) {
                                                   // Opcional: Agregar un indicador de carga
                                               } else {
                                                   // Opcional: Retire el indicador de carga
                                               }
                                           }
                                      });
                                      calendar.render();
                                  } else {
                                       console.error("Calendar container element #calendarView not found.");
                                  }


                              } else {
                                  console.error("FullCalendar library not loaded.");
                              }
                          });
                      </script>


                      <?php if (empty($events)): ?>
                          <div class="mt-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                              No events found matching your criteria to display on the calendar.
                          </div>
                      <?php endif; ?>
                  </div>
              <?php endif; ?> <?php endif; ?> </main>
  <?php
  // Cerrar la conexión de la base de datos
  if (isset($conn)) {
      mysqli_close($conn);
  }
  ?>

<?php include "inc/footer.php"?>