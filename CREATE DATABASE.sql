CREATE DATABASE IF NOT EXISTS `projectdb` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `projectdb`;

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `cid` int(11) NOT NULL,
  `cname` varchar(255) NOT NULL,
  `cpassword` varchar(255) NOT NULL,
  `ctel` varchar(20) DEFAULT NULL,
  `caddr` varchar(255) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `cemail` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

TRUNCATE TABLE `customers`;
INSERT INTO `customers` (`cid`, `cname`, `cpassword`, `ctel`, `caddr`, `company`, `cemail`) VALUES
(3, 'ahhei', '$2y$10$DZnBePiKcitWzCPiyzaVCOgO6iOKTKamZt0Kz1sK/n4dLHETm8Sny', '90646894', 'Hk', '', 'hocheukhei821@gmail.com');

DROP TABLE IF EXISTS `furniturematerials`;
CREATE TABLE `furniturematerials` (
  `fid` int(11) NOT NULL,
  `mid` int(11) NOT NULL,
  `pmqty` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

TRUNCATE TABLE `furniturematerials`;
INSERT INTO `furniturematerials` (`fid`, `mid`, `pmqty`) VALUES
(1, 1, 2),
(2, 1, 10),
(3, 1, 5),
(3, 3, 10),
(3, 4, 3),
(4, 1, 15),
(5, 1, 4),
(5, 2, 6),
(6, 1, 12);

DROP TABLE IF EXISTS `furnitures`;
CREATE TABLE `furnitures` (
  `fid` int(11) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `fdesc` varchar(255) NOT NULL,
  `fprice` decimal(10,2) NOT NULL,
  `fimage` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

TRUNCATE TABLE `furnitures`;
INSERT INTO `furnitures` (`fid`, `fname`, `fdesc`, `fprice`, `fimage`) VALUES
(1, 'Oak Dining Chair', 'Classic style dining chair made of solid oak.', 450.00, 'images/Oak Dining Chair.png'),
(2, 'Large Dining Table', '6-seater dining table, perfect for families.', 2500.00, 'images/Large Dining Table.png'),
(3, 'Seater Fabric Sofa', 'Comfortable grey fabric sofa with foam filling.', 3800.00, 'images/Seater Fabric Sofa.png'),
(4, 'Wooden Wardrobe', 'Double door wardrobe with hanging space.', 1800.00, 'images/Wooden Wardrobe.png'),
(5, 'Industrial Bookshelf', 'Modern style bookshelf with steel frame.', 1200.00, 'images/Industrial Bookshelf.png'),
(6, 'Queen Size Bed Frame', 'Sturdy bed frame for queen size mattress.', 2200.00, 'images/Queen Size Bed Frame.png');

DROP TABLE IF EXISTS `materials`;
CREATE TABLE `materials` (
  `mid` int(11) NOT NULL,
  `mname` varchar(255) NOT NULL,
  `mqty` int(11) NOT NULL DEFAULT 0,
  `munit` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

TRUNCATE TABLE `materials`;
INSERT INTO `materials` (`mid`, `mname`, `mqty`, `munit`) VALUES
(1, 'Oak Wood Plank', 492, 'pcs'),
(2, 'Steel Tube', 194, 'meter'),
(3, 'Fabric Cloth', 100, 'meter'),
(4, 'High Density Foam', 50, 'block');

DROP TABLE IF EXISTS `orderfurnitures`;
CREATE TABLE `orderfurnitures` (
  `oid` int(11) NOT NULL,
  `fid` int(11) NOT NULL,
  `oqty` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

TRUNCATE TABLE `orderfurnitures`;
INSERT INTO `orderfurnitures` (`oid`, `fid`, `oqty`) VALUES
(1, 1, 2),
(1, 5, 1);

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `oid` int(11) NOT NULL,
  `odate` datetime NOT NULL DEFAULT current_timestamp(),
  `ototalamount` decimal(10,2) NOT NULL,
  `cid` int(11) NOT NULL,
  `odeliverydate` datetime NOT NULL,
  `odeliveraddress` text NOT NULL,
  `ostatus` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

TRUNCATE TABLE `orders`;
INSERT INTO `orders` (`oid`, `odate`, `ototalamount`, `cid`, `odeliverydate`, `odeliveraddress`, `ostatus`) VALUES
(1, '2026-02-15 20:31:58', 2100.00, 3, '2026-02-18 18:00:00', '0', 1);

DROP TABLE IF EXISTS `staffs`;
CREATE TABLE `staffs` (
  `sid` int(11) NOT NULL,
  `spassword` varchar(255) NOT NULL,
  `sname` varchar(255) NOT NULL,
  `srole` varchar(50) NOT NULL,
  `stel` varchar(20) NOT NULL,
  `sstatus` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

TRUNCATE TABLE `staffs`;
INSERT INTO `staffs` (`sid`, `spassword`, `sname`, `srole`, `stel`, `sstatus`) VALUES
(2, 'test01', 'test01', 'admin', '12345678', 1),
(3, '$2y$10$s5J/bhc/.C01Jl8p0uvvXe0KYG2EuRA7y3Vv2dy0BOKxNJzxUu7F2', 'Admin01', 'admin', '', 1),
(4, '$2y$10$xpImcNQPbo57wKxf28bKW.GCFBm1CvCMSDGqaPTcHdymJeJkJnSi.', 'test02', 'staff', '', 1);


ALTER TABLE `customers`
  ADD PRIMARY KEY (`cid`);

ALTER TABLE `furniturematerials`
  ADD PRIMARY KEY (`fid`,`mid`),
  ADD KEY `mid` (`mid`);

ALTER TABLE `furnitures`
  ADD PRIMARY KEY (`fid`);

ALTER TABLE `materials`
  ADD PRIMARY KEY (`mid`);

ALTER TABLE `orderfurnitures`
  ADD PRIMARY KEY (`fid`,`oid`),
  ADD KEY `oid` (`oid`);

ALTER TABLE `orders`
  ADD PRIMARY KEY (`oid`),
  ADD KEY `cid` (`cid`);

ALTER TABLE `staffs`
  ADD PRIMARY KEY (`sid`);


ALTER TABLE `customers`
  MODIFY `cid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `furnitures`
  MODIFY `fid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `materials`
  MODIFY `mid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `orders`
  MODIFY `oid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `staffs`
  MODIFY `sid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;


ALTER TABLE `furniturematerials`
  ADD CONSTRAINT `furniturematerials_ibfk_1` FOREIGN KEY (`fid`) REFERENCES `furnitures` (`fid`),
  ADD CONSTRAINT `furniturematerials_ibfk_2` FOREIGN KEY (`mid`) REFERENCES `materials` (`mid`);

ALTER TABLE `orderfurnitures`
  ADD CONSTRAINT `orderfurnitures_ibfk_1` FOREIGN KEY (`fid`) REFERENCES `furnitures` (`fid`),
  ADD CONSTRAINT `orderfurnitures_ibfk_2` FOREIGN KEY (`oid`) REFERENCES `orders` (`oid`);

ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`cid`) REFERENCES `customers` (`cid`);
COMMIT;
