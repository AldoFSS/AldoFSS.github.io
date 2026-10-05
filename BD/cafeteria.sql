-- MariaDB dump 10.19  Distrib 10.4.24-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: cafeteria
-- ------------------------------------------------------
-- Server version	10.4.24-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `detallesorden`
--

DROP TABLE IF EXISTS `detallesorden`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detallesorden` (
  `ID_Orden` int(11) NOT NULL,
  `ID_Productos` int(11) NOT NULL,
  `Cantidad` int(11) NOT NULL,
  PRIMARY KEY (`ID_Orden`,`ID_Productos`),
  KEY `ID_Productos` (`ID_Productos`),
  CONSTRAINT `detallesorden_ibfk_1` FOREIGN KEY (`ID_Productos`) REFERENCES `menuproductos` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `detallesorden_ibfk_2` FOREIGN KEY (`ID_Orden`) REFERENCES `ordenes` (`ID_Ordenes`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detallesorden`
--

LOCK TABLES `detallesorden` WRITE;
/*!40000 ALTER TABLE `detallesorden` DISABLE KEYS */;
INSERT INTO `detallesorden` VALUES (225,24,1),(226,24,1),(227,24,1),(228,24,1),(229,24,1),(230,24,1),(231,24,1),(232,24,1),(233,24,1),(234,25,1),(235,25,1),(236,28,1),(237,25,1),(238,28,1),(239,25,1),(240,25,1),(241,24,1),(242,24,1),(243,28,1),(244,25,1),(245,25,1),(246,24,1),(247,28,1),(248,25,1),(249,25,1),(250,24,1),(250,25,1),(251,28,1),(252,24,3),(252,25,1),(252,26,1),(252,32,3),(252,36,3),(253,31,1),(254,24,1),(254,25,1),(254,29,1),(254,33,3);
/*!40000 ALTER TABLE `detallesorden` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menucategoria`
--

DROP TABLE IF EXISTS `menucategoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menucategoria` (
  `Categoria` varchar(50) NOT NULL,
  `ID_Cat` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID_Cat`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menucategoria`
--

LOCK TABLES `menucategoria` WRITE;
/*!40000 ALTER TABLE `menucategoria` DISABLE KEYS */;
INSERT INTO `menucategoria` VALUES ('Bebidas',1),('Desayuno',2),('Antojitos',3),('Alimentos',4),('Snack',9);
/*!40000 ALTER TABLE `menucategoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menuproductos`
--

DROP TABLE IF EXISTS `menuproductos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menuproductos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Product_Nombre` varchar(255) NOT NULL,
  `Descripcion` text NOT NULL,
  `Costo` decimal(10,2) NOT NULL,
  `Imagen` varchar(255) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_categoria` (`id_categoria`),
  CONSTRAINT `menuproductos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `menucategoria` (`ID_Cat`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menuproductos`
--

LOCK TABLES `menuproductos` WRITE;
/*!40000 ALTER TABLE `menuproductos` DISABLE KEYS */;
INSERT INTO `menuproductos` VALUES (24,'Frappe','',80.23,'../PRODUCTOS/Frappe.jpg',1),(25,'Sandwich','jamon',23.70,'../PRODUCTOS/Sandwich.jpg',2),(26,'Pastel Imposible','chocolate, vainilla',45.32,'../PRODUCTOS/Pastel Imposible.jpg',2),(28,'Creapa','Nutella, Salada',34.67,'../PRODUCTOS/Creapa.jpg',2),(29,'Guajolote','pierna, salchicha, jamon',45.00,'../PRODUCTOS/Guajolote.jpg',3),(31,'Malteada de Chocolate','Fresas,chsipitas',43.79,'../PRODUCTOS/Malteada de Chocolate.jpg',1),(32,'Cuernito','',44.60,'../PRODUCTOS/Cuernito.jpg',2),(33,'Torta','Jamon',25.00,'../PRODUCTOS/Torta.jpg',9),(35,'Paste','',34.00,'../PRODUCTOS/Paste.jpg',2),(36,'Pastel heladp','',89.00,'../PRODUCTOS/Pastel heladp.jpg',2);
/*!40000 ALTER TABLE `menuproductos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ordenes`
--

DROP TABLE IF EXISTS `ordenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ordenes` (
  `ID_Ordenes` int(11) NOT NULL AUTO_INCREMENT,
  `ID_Usuario` int(11) NOT NULL,
  `Total` double NOT NULL,
  `Fecha_Compra` date NOT NULL,
  PRIMARY KEY (`ID_Ordenes`),
  KEY `ID_Usuario` (`ID_Usuario`),
  CONSTRAINT `ordenes_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuarios` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=255 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ordenes`
--

LOCK TABLES `ordenes` WRITE;
/*!40000 ALTER TABLE `ordenes` DISABLE KEYS */;
INSERT INTO `ordenes` VALUES (225,20,80.23,'2024-10-21'),(226,20,80.23,'2024-10-21'),(227,20,80.23,'2024-10-21'),(228,20,80.23,'2024-10-21'),(229,20,80.23,'2024-10-21'),(230,20,80.23,'2024-10-21'),(231,20,80.23,'2024-10-21'),(232,20,80.23,'2024-10-21'),(233,20,80.23,'2024-10-21'),(234,20,23.7,'2024-10-21'),(235,20,23.7,'2024-10-21'),(236,20,34.67,'2024-10-21'),(237,20,23.7,'2024-10-21'),(238,20,34.67,'2024-10-21'),(239,20,23.7,'2024-10-21'),(240,20,23.7,'2024-10-21'),(241,20,80.23,'2024-10-21'),(242,20,80.23,'2024-10-21'),(243,20,34.67,'2024-10-21'),(244,20,23.7,'2024-10-21'),(245,20,23.7,'2024-10-21'),(246,20,80.23,'2024-10-21'),(247,20,34.67,'2024-10-21'),(248,20,23.7,'2024-10-21'),(249,20,23.7,'2024-10-21'),(250,20,103.93,'2024-10-21'),(251,20,34.67,'2024-10-21'),(252,20,710.51,'2024-10-21'),(253,20,43.79,'2024-10-21'),(254,20,223.93,'2024-10-21');
/*!40000 ALTER TABLE `ordenes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(50) NOT NULL,
  `Contraseña` varchar(50) NOT NULL,
  `Cargo` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (20,'Lupita','202cb962ac59075b964b07152d234b70','administrador'),(30,'admin','21232f297a57a5a743894a0e4a801fc3','admin'),(31,'Aldo','25f9e794323b453885f5181f1b624d0b','usuario');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-10-22 15:50:23
