-- Crear la BBDD
DROP DATABASE `events_db`;
CREATE DATABASE IF NOT EXISTS `events_db`;
USE `events_db`;

-- Crear la tabla Categorias
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL
);

-- Crear la tabla Eventos
CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `event_date` date NOT NULL,
  `location` varchar(100) NOT NULL,
  `image_url` varchar(100) NULL,
  `category_id` int NOT NULL,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
);

-- Inserción de datos
INSERT INTO `categories` (name) VALUES
('Conciertos'),
('Deportes'),
('Teatro'),
('Cultura'),
('Feria');

INSERT INTO `events` (title, description, event_date, location, image_url, category_id) VALUES
('Concierto de Rock', 'Una noche llena de música rock con bandas locales.', '2025-04-15', 'Auditorio Municipal', 'festival_rock.jpg', 1),
('Maratón Anual', 'Participa en el maratón más emocionante del año.', '2025-04-20', 'Parque Central', 'maraton_anual.jpg', 2),
('Obra de Teatro: Hamlet', 'Una interpretación moderna de la clásica obra de Shakespeare.', '2025-04-10', 'Teatro Nacional', 'teatro_hamlet.jpg', 3),
('Exposición de Arte', 'Exposición de artistas locales en la galería de arte.', '2025-04-01', 'Galería de Arte', 'exposicion_arte.jpg', 4),
('Feria de Comida', 'Disfruta de una variedad de comidas de todo el mundo.', '2025-04-12', 'Plaza Principal', 'festival_comida.jpg', 5);

