-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 05. Okt 2013 um 17:04
-- Server Version: 5.5.32
-- PHP-Version: 5.4.20-1+debphp.org~quantal+1

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `sulu`
--

--
-- Daten für Tabelle `tr_catalogues`
--

INSERT INTO `tr_catalogues` (`id`, `locale`, `isDefault`, `idPackages`) VALUES
(3, 'DE', 1, 1),
(4, 'EN', 0, 1),
(10, 'FR', 0, 1),
(11, 'NED', 0, 1);

--
-- Daten für Tabelle `tr_codes`
--

INSERT INTO `tr_codes` (`id`, `code`, `backend`, `frontend`, `length`, `idLocations`, `idPackages`) VALUES
(4, 'public.name', 1, 0, 20, NULL, 1),
(6, 'public.edit', 1, 0, 22, NULL, 1),
(7, 'header.add', 1, 0, 20, NULL, 1),
(8, 'header.save', 1, 0, 20, NULL, 1),
(9, 'header.saved', 1, 0, 20, NULL, 1),
(10, 'header.delete', 1, 0, 20, NULL, 1),
(11, 'navigation.list', 1, 0, 20, NULL, 1),
(12, 'translate.package.title', 1, 0, 20, NULL, 1),
(13, 'translate.package.catalogues', 1, 0, 20, NULL, 1),
(14, 'translate.package.settings.locale', 1, 0, 20, NULL, 1),
(15, 'translate.package.settings.addCatalogue', 1, 0, 20, NULL, 1),
(16, 'translate.package.details.key', 1, 0, 20, NULL, 1),
(17, 'translate.package.details.translation', 1, 0, 20, NULL, 1),
(18, 'translate.package.details.suggestion', 1, 0, 20, NULL, 1),
(19, 'translate.package.details.maxLength', 1, 0, 20, NULL, 1),
(20, 'translate.package.details.length', 1, 0, 20, NULL, 1),
(21, 'translate.package.details.frontend', 1, 0, 20, NULL, 1),
(22, 'translate.package.details.backend', 1, 0, 20, NULL, 1),
(23, 'translate.package.details.addElement', 1, 0, 20, NULL, 1),
(24, 'public.delete', 1, 0, 20, NULL, 1),
(25, 'translate.package.settings.title', 1, 0, 20, NULL, 1),
(26, 'contact.contacts.title', 1, 0, 20, NULL, 1),
(27, 'contact.contacts.contactTitle', 1, 0, 20, NULL, 1),
(28, 'contact.contacts.firstName', 1, 0, 20, NULL, 1),
(29, 'contact.contacts.lastName', 1, 0, 20, NULL, 1),
(30, 'contact.contacts.position', 1, 0, 20, NULL, 1),
(31, 'contact.contacts.company', 1, 0, 20, NULL, 1),
(32, 'contact.form.addEmail', 1, 0, 20, NULL, 1),
(33, 'contact.form.addPhone', 1, 0, 20, NULL, 1),
(34, 'contact.form.addAddress', 1, 0, 20, NULL, 1),
(35, 'contact.address.street', 1, 0, 20, NULL, 1),
(36, 'contact.address.number', 1, 0, 20, NULL, 1),
(37, 'contact.address.additional', 1, 0, 20, NULL, 1),
(38, 'contact.address.zip', 1, 0, 20, NULL, 1),
(39, 'contact.address.city', 1, 0, 20, NULL, 1),
(40, 'contact.address.state', 1, 0, 20, NULL, 1),
(41, 'contact.address.country', 1, 0, 20, NULL, 1),
(42, 'public.email', 1, 0, 20, NULL, 1),
(43, 'public.phone', 1, 0, 20, NULL, 1),
(44, 'public.address', 1, 0, 20, NULL, 1),
(45, 'email.home', 1, 0, 20, NULL, 1),
(46, 'email.work', 1, 0, 20, NULL, 1),
(47, 'phone.home', 1, 0, 20, NULL, 1),
(48, 'phone.work', 1, 0, 20, NULL, 1),
(49, 'address.home', 0, 0, 20, NULL, 1),
(50, 'address.work', 0, 0, 20, NULL, 1),
(51, 'country.austria', 1, 0, 20, NULL, 1),
(52, 'country.germany', 1, 0, 20, NULL, 1),
(53, 'contact.accounts.title', 1, 0, 20, NULL, 1),
(54, 'contact.accounts.name', 1, 0, 20, NULL, 1),
(55, 'contact.accounts.website', 1, 0, 20, NULL, 1),
(56, 'contact.accounts.company', 1, 0, 40, NULL, 1);

--
-- Daten für Tabelle `tr_packages`
--

INSERT INTO `tr_packages` (`id`, `name`) VALUES
(1, 'sulu');

--
-- Daten für Tabelle `tr_translations`
--

INSERT INTO `tr_translations` (`value`, `idCatalogues`, `idCodes`) VALUES
('Name', 3, 4),
('Editieren', 3, 6),
('Hinzufügen', 3, 7),
('Speichern', 3, 8),
('Gespeichert', 3, 9),
('Löschen', 3, 10),
('Zurück zur Liste', 3, 11),
('Packet', 3, 12),
('Sprachen Kataloge', 3, 13),
('Region', 3, 14),
('Region hinzufügen', 3, 15),
('Code', 3, 16),
('Übersetzung', 3, 17),
('Vorschlag', 3, 18),
('Maximale Länge', 3, 19),
('Länge', 3, 20),
('Frontend', 3, 21),
('Backend', 3, 22),
('Element Hinzufügen', 3, 23),
('Löschen', 3, 24),
('Titel', 3, 25),
('Kontakt', 3, 26),
('Titel', 3, 27),
('Vorname', 3, 28),
('Lastname', 3, 29),
('Position', 3, 30),
('Unternehmen', 3, 31),
('E-Mail hinzufügen', 3, 32),
('Telefon hinzufügen', 3, 33),
('Addresse hinzufügen', 3, 34),
('Straße', 3, 35),
('Nummer', 3, 36),
('Haus, Stock, etc.', 3, 37),
('Postleitzahl', 3, 38),
('Stadt', 3, 39),
('Staat', 3, 40),
('Land', 3, 41),
('E-Mail', 3, 42),
('Telefon', 3, 43),
('Adresse', 3, 44),
('Persöhnlich', 3, 45),
('Geschäftlich', 3, 46),
('Persöhnlich', 3, 47),
('Geschäftlich', 3, 48),
('Persöhnlich', 3, 49),
('Geschäftlich', 3, 50),
('Österreich', 3, 51),
('Deutschland', 3, 52),
('Account', 3, 53),
('Name', 3, 54),
('Internetseite', 3, 55),
('Übergeordnetes Unternehmen', 3, 56),
('Name', 4, 4),
('Edit', 4, 6),
('Add', 4, 7),
('Save', 4, 8),
('Saved', 4, 9),
('Delete', 4, 10),
('Back to list', 4, 11),
('Package', 4, 12),
('Language Catalogues', 4, 13),
('Locale', 4, 14),
('Add Catalogue', 4, 15),
('Key', 4, 16),
('Translation', 4, 17),
('Suggestion', 4, 18),
('Maximum length', 4, 19),
('Length', 4, 20),
('Frontend', 4, 21),
('Backend', 4, 22),
('Add element', 4, 23),
('Delete', 4, 24),
('Title', 4, 25);
SET FOREIGN_KEY_CHECKS=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
