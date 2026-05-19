-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 10.123.0.243:3306
-- Время создания: Май 19 2026 г., 09:55
-- Версия сервера: 8.4.7
-- Версия PHP: 8.2.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `olhlar_craftbox`
--
CREATE DATABASE IF NOT EXISTS `olhlar_craftbox` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `olhlar_craftbox`;

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `items_json` text COLLATE utf8mb4_unicode_ci,
  `status` enum('new','processing','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `delivery_interval` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `customer_name`, `phone`, `address`, `total_price`, `items_json`, `status`, `created_at`, `delivery_interval`) VALUES
(82, NULL, 'Сайма🫶🏻', '+380500635285', 'Кур\'єр: м. Sex, вул. Seven, буд. Sex, кв. Nine | Оплата: При отриманні', 869.98, '[{\"product_id\":\"34\",\"title\":\"Бурмалда\",\"price\":\"434.99\",\"quantity\":2}]', 'new', '2026-05-19 09:46:06', 'none');

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `seller_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `weight` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_file_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`id`, `seller_id`, `title`, `description`, `weight`, `price`, `image_path`, `google_file_id`) VALUES
(34, 41, 'Бурмалда', 'Тут лягаєш бурмалдишь', '453', 434.99, 'uploads/1779183467_product.png', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('buyer','seller') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_code` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `verification_code`, `is_verified`) VALUES
(40, 'Arizonchik337', 'vladyslav.mizin@gmail.com', 'google_auth', 'seller', NULL, 0),
(41, 'EuroPenetrator', 'vladyslav.mizin228@gmail.com', '$2y$10$rNtOMZq2T5Njb0OpPIg/fedhk0LfmRTzRZfr3hsXx.AcUzdGjdI.m', 'seller', NULL, 0),
(42, 'ghag', 'arizonchik337@gmail.com', '$2y$10$NMZYjwrfRB5IHDfacRP7juL9rswSX2CPCOQB1789QnqJhEa/KsjIe', 'buyer', NULL, 0),
(43, 'Роман', 'myroshchnichenko213@gmail.com', 'google_auth', 'buyer', NULL, 0),
(44, 'User Mobile', 'comfy7437s@gmail.com', 'google_auth', 'seller', NULL, 0),
(45, 'Ольга Ларина', 'olarina597@gmail.com', 'google_auth', 'buyer', NULL, 0),
(46, 'Олег', 'tytert@gmail.com', '$2y$10$ryXhh4EKWHaYmJ4Z6g1dbe.v4q/25h/oJrkvCOlo4LStAJOs5j9xC', 'seller', NULL, 0);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `buyer_id` (`user_id`);

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
