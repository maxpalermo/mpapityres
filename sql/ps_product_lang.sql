-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Creato il: Set 26, 2025 alle 11:40
-- Versione del server: 11.4.8-MariaDB-cll-lve-log
-- Versione PHP: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nngservice22it_prestashop`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `ps_product_lang`
--

CREATE TABLE `ps_product_lang` (
  `id_product` int(10) UNSIGNED NOT NULL,
  `id_shop` int(11) UNSIGNED NOT NULL DEFAULT 1,
  `id_lang` int(10) UNSIGNED NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `description_short` mediumtext DEFAULT NULL,
  `link_rewrite` varchar(128) NOT NULL,
  `meta_description` varchar(512) DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `meta_title` varchar(128) DEFAULT NULL,
  `name` varchar(128) NOT NULL,
  `available_now` varchar(255) DEFAULT NULL,
  `available_later` varchar(255) DEFAULT NULL,
  `delivery_in_stock` varchar(255) DEFAULT NULL,
  `delivery_out_stock` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `ps_product_lang`
--

INSERT INTO `ps_product_lang` (`id_product`, `id_shop`, `id_lang`, `description`, `description_short`, `link_rewrite`, `meta_description`, `meta_keywords`, `meta_title`, `name`, `available_now`, `available_later`, `delivery_in_stock`, `delivery_out_stock`) VALUES
(10000000, 1, 1, '<p>I PFU, acronimo di Pneumatici Fuori Uso, sono gli pneumatici che non sono più utilizzabili sui veicoli e non vengono più rigenerati o riutilizzati.</p>\n<p> Questi rifiuti, generati dalla dismissione di pneumatici da auto, moto o mezzi pesanti, sono costituiti da gomma, acciaio e fibre tessili, materiali che possono essere recuperati e riutilizzati in diversi settori.</p>\n<p> In Italia, il contributo PFU è obbligatorio e viene applicato al momento dell\'acquisto di pneumatici nuovi o di veicoli nuovi con pneumatici già installati, coprendo i costi futuri dello smaltimento.</p>\n<p> Il contributo è prepagato e viene versato a consorzi come Ecopneus ed Ecotyre, che gestiscono la raccolta, il trasporto e il trattamento dei PFU per il riciclo o il recupero energetico.</p>', '<p>Contributo Ambientale FPU</p>', 'fpu-500-eur', '', '', '', 'FPU 5.00 EUR', '', '', '', '');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `ps_product_lang`
--
ALTER TABLE `ps_product_lang`
  ADD PRIMARY KEY (`id_product`,`id_shop`,`id_lang`),
  ADD KEY `id_lang` (`id_lang`),
  ADD KEY `name` (`name`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
