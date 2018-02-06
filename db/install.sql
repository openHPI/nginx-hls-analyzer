SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `nginxhlslog`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `connections`
--

CREATE TABLE `connections` (
  `id` int(11) NOT NULL,
  `c-client-id` varchar(100) NOT NULL,
  `c-ip` varchar(30) NOT NULL,
  `c-agent` TEXT NOT NULL,
  `c-ip-country` varchar(30) NOT NULL,
  `streamname` varchar(100) NOT NULL,
  `timestamp-start` datetime NOT NULL,
  `timestamp-end` datetime NOT NULL,
  `bytes` int(11) NOT NULL,
  `duration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `last_update`
--

CREATE TABLE `last_update` (
  `time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `last_update`
--

INSERT INTO `last_update` (`time`) VALUES
('1970-01-01 12:00:00');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log`
--

CREATE TABLE `log` (
  `id` int(11) NOT NULL,
  `c-client-id` varchar(100) DEFAULT NULL,
  `c-client-id-conn` varchar(100) NOT NULL,
  `c-ip` varchar(30) DEFAULT NULL,
  `c-agent` TEXT NOT NULL,
  `c-ip-country` varchar(30) DEFAULT NULL,
  `streamname` varchar(100) DEFAULT NULL,
  `streamquality` varchar(100) DEFAULT NULL,
  `connection-id` varchar(100) NOT NULL,
  `timestamp` datetime DEFAULT NULL,
  `bytes` int(11) DEFAULT NULL,
  `evaluated` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `connections`
--
ALTER TABLE `connections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `c-client-id` (`c-client-id`);

--
-- Indizes für die Tabelle `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timestamp` (`timestamp`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `connections`
--
ALTER TABLE `connections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT für Tabelle `log`
--
ALTER TABLE `log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3023;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
