-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 20-08-2025 a las 17:47:21
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `materiales_l_y_m`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Caja_Diaria`
--

CREATE TABLE `Caja_Diaria` (
  `id_caja` int(11) NOT NULL,
  `fecha_apertura` datetime NOT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `saldo_inicial` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ingresos` decimal(12,2) NOT NULL DEFAULT 0.00,
  `egresos` decimal(12,2) NOT NULL DEFAULT 0.00,
  `saldo_final` decimal(12,2) NOT NULL DEFAULT 0.00,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Caja_Diaria`
--

INSERT INTO `Caja_Diaria` (`id_caja`, `fecha_apertura`, `fecha_cierre`, `saldo_inicial`, `ingresos`, `egresos`, `saldo_final`, `id_usuario`) VALUES
(1, '2025-08-10 15:24:20', NULL, 1000.00, 500.00, 200.00, 1300.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Categoria_Producto`
--

CREATE TABLE `Categoria_Producto` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Categoria_Producto`
--

INSERT INTO `Categoria_Producto` (`id_categoria`, `nombre`, `descripcion`) VALUES
(1, 'Herramientas', 'Herramientas manuales y eléctricas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Cliente`
--

CREATE TABLE `Cliente` (
  `id_cliente` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `cuit` varchar(20) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Cliente`
--

INSERT INTO `Cliente` (`id_cliente`, `nombre`, `apellido`, `cuit`, `telefono`, `email`, `direccion`, `fecha_registro`) VALUES
(1, 'Juan', 'Pérez', '12345678', '555-1234', 'juan.perez@example.com', 'Calle Falsa 123', '2025-08-10 15:24:20'),
(2, 'sergio', 'merfa', '23445433', '23566454', 'fjsaka@gmail.com', 'dlñalañd', '2025-08-11 10:30:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Compra`
--

CREATE TABLE `Compra` (
  `id_compra` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `id_proveedor` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `numero_factura` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Compra`
--

INSERT INTO `Compra` (`id_compra`, `fecha`, `id_proveedor`, `id_usuario`, `total`, `numero_factura`, `observaciones`) VALUES
(1, '2025-08-10 15:24:20', 1, 1, 1000.00, NULL, NULL),
(2, '2025-08-14 00:00:00', 2, 1, 290.29, NULL, NULL),
(5, '2025-08-14 00:00:00', 2, 1, 9666.29, '', ''),
(6, '2025-08-14 00:00:00', 2, 1, 0.00, '', ''),
(7, '2025-08-14 00:00:00', 2, 1, 0.00, '', ''),
(8, '2025-08-14 00:00:00', 2, 1, 0.00, '', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Comprobante`
--

CREATE TABLE `Comprobante` (
  `id_comprobante` int(11) NOT NULL,
  `id_venta` int(11) DEFAULT NULL,
  `tipo_comprobante` enum('Factura A','Factura B','Ticket','Nota de Crédito') NOT NULL,
  `punto_venta` int(11) NOT NULL,
  `numero_comprobante` bigint(20) NOT NULL,
  `fecha_emision` datetime DEFAULT current_timestamp(),
  `cae` varchar(50) DEFAULT NULL,
  `fecha_vencimiento_cae` datetime DEFAULT NULL,
  `estado` enum('Autorizado','Rechazado','Pendiente') NOT NULL,
  `monto_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Comprobante`
--

INSERT INTO `Comprobante` (`id_comprobante`, `id_venta`, `tipo_comprobante`, `punto_venta`, `numero_comprobante`, `fecha_emision`, `cae`, `fecha_vencimiento_cae`, `estado`, `monto_total`) VALUES
(1, 1, 'Factura B', 1, 12345678, '2025-08-10 15:24:20', 'CAEX123456789', '2025-09-09 15:24:20', 'Autorizado', 150.00),
(2, 7, 'Factura B', 1, 19, '2025-08-19 23:53:13', '75339269103847', '2025-08-30 00:00:00', 'Autorizado', 0.00),
(3, 8, 'Factura B', 1, 20, '2025-08-19 23:55:47', '75339269103876', '2025-08-30 00:00:00', 'Autorizado', 0.00),
(4, 9, 'Factura B', 1, 21, '2025-08-19 23:58:43', '75339269103907', '2025-08-30 00:00:00', 'Autorizado', 0.00),
(5, 10, 'Factura B', 1, 22, '2025-08-20 00:05:53', '75349269104040', '2025-08-30 00:00:00', 'Autorizado', 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Detalle_Compra`
--

CREATE TABLE `Detalle_Compra` (
  `id_detalle_compra` int(11) NOT NULL,
  `id_compra` int(11) DEFAULT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL DEFAULT 0.00,
  `lote` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Detalle_Compra`
--

INSERT INTO `Detalle_Compra` (`id_detalle_compra`, `id_compra`, `id_producto`, `cantidad`, `precio_unitario`, `lote`) VALUES
(1, 1, 1, 10, 100.00, NULL),
(2, 2, 8, 1, 290.29, NULL),
(3, 5, 7, 4, 2344.00, NULL),
(4, 5, 8, 1, 290.29, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Detalle_Comprobante`
--

CREATE TABLE `Detalle_Comprobante` (
  `id_detalle_comprobante` int(11) NOT NULL,
  `id_comprobante` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL,
  `alicuota_iva` decimal(5,2) DEFAULT NULL,
  `importe_iva` decimal(12,2) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  `id_iva` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Detalle_Comprobante`
--

INSERT INTO `Detalle_Comprobante` (`id_detalle_comprobante`, `id_comprobante`, `descripcion`, `cantidad`, `precio_unitario`, `alicuota_iva`, `importe_iva`, `subtotal`, `id_iva`) VALUES
(1, 1, 'Venta Martillo', 1, 150.00, 21.00, 31.50, 150.00, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Detalle_Venta`
--

CREATE TABLE `Detalle_Venta` (
  `id_detalle_venta` int(11) NOT NULL,
  `id_venta` int(11) DEFAULT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Detalle_Venta`
--

INSERT INTO `Detalle_Venta` (`id_detalle_venta`, `id_venta`, `id_producto`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 1, 1, 150.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Inventario_Movimientos`
--

CREATE TABLE `Inventario_Movimientos` (
  `id_movimiento` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `tipo` enum('Entrada','Salida','Ajuste') NOT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `costo_unitario` decimal(12,2) DEFAULT NULL,
  `nro_lote` varchar(50) DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Inventario_Movimientos`
--

INSERT INTO `Inventario_Movimientos` (`id_movimiento`, `fecha`, `tipo`, `id_producto`, `cantidad`, `costo_unitario`, `nro_lote`, `fecha_vencimiento`, `motivo`) VALUES
(1, '2025-08-10 15:24:20', 'Entrada', 1, 10, NULL, NULL, NULL, 'Compra inicial');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Marca`
--

CREATE TABLE `Marca` (
  `id_marca` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `origen` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Marca`
--

INSERT INTO `Marca` (`id_marca`, `nombre`, `origen`) VALUES
(1, 'MarcaX', 'Argentina');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Medio_Pago`
--

CREATE TABLE `Medio_Pago` (
  `id_medio_pago` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('Contado','Tarjeta','Transferencia','Pago Electrónico','Cheque','Crédito','Personalizado') NOT NULL DEFAULT 'Contado',
  `requiere_datos` enum('Sí','No') NOT NULL,
  `permite_cuotas` tinyint(1) NOT NULL DEFAULT 0,
  `max_cuotas` int(11) NOT NULL DEFAULT 1,
  `interes_cuota` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Medio_Pago`
--

INSERT INTO `Medio_Pago` (`id_medio_pago`, `nombre`, `tipo`, `requiere_datos`, `permite_cuotas`, `max_cuotas`, `interes_cuota`) VALUES
(1, 'Efectivo', 'Contado', 'No', 0, 1, 0.00),
(2, 'Tarjeta de Crédito', 'Tarjeta', 'Sí', 1, 12, 2.50),
(3, 'Tarjeta de Débito', 'Tarjeta', 'Sí', 1, 6, 1.50),
(4, 'Transferencia Bancaria', 'Transferencia', 'Sí', 0, 1, 0.00),
(5, 'Mercado Pago', 'Pago Electrónico', 'No', 1, 6, 1.50),
(6, 'Cheque', 'Cheque', 'Sí', 0, 1, 0.00),
(7, 'Vale', 'Crédito', 'No', 0, 1, 0.00),
(8, 'Otro', 'Personalizado', 'No', 0, 1, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pago_Venta`
--

CREATE TABLE `Pago_Venta` (
  `id_pago` int(11) NOT NULL,
  `id_venta` int(11) DEFAULT NULL,
  `id_medio_pago` int(11) DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL,
  `referencia` varchar(255) DEFAULT NULL,
  `fecha_pago` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Pago_Venta`
--

INSERT INTO `Pago_Venta` (`id_pago`, `id_venta`, `id_medio_pago`, `monto`, `referencia`, `fecha_pago`) VALUES
(2, 1, 1, 500.00, NULL, '2025-08-11 22:43:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Producto`
--

CREATE TABLE `Producto` (
  `id_producto` int(11) NOT NULL,
  `codigo_barras` varchar(50) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_compra` decimal(12,2) NOT NULL DEFAULT 0.00,
  `precio_venta` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `imagen` varchar(255) DEFAULT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `u_medida` varchar(50) DEFAULT NULL,
  `id_categoria` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Producto`
--

INSERT INTO `Producto` (`id_producto`, `codigo_barras`, `nombre`, `descripcion`, `precio_compra`, `precio_venta`, `stock`, `stock_minimo`, `activo`, `imagen`, `id_proveedor`, `marca`, `u_medida`, `id_categoria`) VALUES
(1, NULL, 'Martillo', 'Martillo de acero', 100.00, 100.00, 10, 2, 1, 'martillo.jpg', 1, NULL, NULL, NULL),
(2, NULL, 'Union PVC 20mm', NULL, 219.00, 100.00, 250, 50, 1, 'union_pvc_20mm.jpg', NULL, 'Kalop', NULL, NULL),
(3, NULL, 'Union PVC 25mm', NULL, 338.00, 100.00, 250, 50, 1, NULL, NULL, 'Kalop', NULL, NULL),
(4, NULL, 'Union PVC 32mm', NULL, 898.00, 100.00, 100, 20, 1, NULL, NULL, 'Kalop', NULL, NULL),
(5, NULL, 'Union PVC 40mm', NULL, 1232.63, 100.00, 50, 20, 1, NULL, NULL, 'Kalop', NULL, NULL),
(6, NULL, 'Union PVC 50mm', NULL, 1707.75, 100.00, 50, 20, 1, NULL, NULL, 'Kalop', NULL, NULL),
(7, NULL, 'Caño PVC 20mm Semipesado', NULL, 349.20, 100.00, 94, 18, 1, NULL, NULL, 'Kalop', NULL, NULL),
(8, NULL, 'Caño PVC 16mm Semipesado', NULL, 290.29, 100.00, 122, 24, 1, NULL, NULL, 'Kalop', NULL, NULL),
(9, NULL, 'Caño PVC 22mm Semipesado', NULL, 469.52, 100.00, 90, 18, 1, NULL, NULL, 'Kalop', NULL, NULL),
(10, NULL, 'Caño PVC 25mm Semipesado', NULL, 492.65, 100.00, 60, 12, 1, NULL, NULL, 'Kalop', NULL, NULL),
(11, NULL, 'Caño PVC 32mm Semipesado', NULL, 728.20, 100.00, 30, 6, 1, NULL, NULL, 'Kalop', NULL, NULL),
(12, NULL, 'Caño PVC 40mm Semipesado', NULL, 1061.59, 100.00, 30, 6, 1, NULL, NULL, 'Kalop', NULL, NULL),
(13, NULL, 'Caño PVC 50mm Semipesado', NULL, 1497.29, 100.00, 30, 6, 1, NULL, NULL, 'Kalop', NULL, NULL),
(14, NULL, 'Caja de embutir Rectangular Metalica Liviana', NULL, 177.92, 100.00, 120, 24, 1, NULL, NULL, 'AG Metalurgica', 'unidades', NULL),
(15, NULL, 'Caja de embutir Octogonal Grande Metalica Liviana', NULL, 416.00, 100.00, 120, 24, 1, NULL, NULL, 'AG Metalurgica', 'unidades', NULL),
(16, NULL, 'Caja de embutir Cuadrada Metalica Liviana', NULL, 497.92, 100.00, 120, 24, 1, NULL, NULL, 'AG Metalurgica', 'unidades', NULL),
(17, NULL, 'Caja de embutir Octogonal Chica Metalica Liviana', NULL, 423.42, 100.00, 120, 24, 1, NULL, NULL, 'AG Metalurgica', 'unidades', NULL),
(18, NULL, 'Caja de embutir Mignon Metalica Liviana', NULL, 177.92, 100.00, 120, 24, 1, NULL, NULL, 'AG Metalurgica', 'unidades', NULL),
(19, NULL, 'Caja de pase 15x15x10 Liviana', NULL, 2883.84, 100.00, 120, 24, 1, NULL, NULL, 'AG Metalurgica', 'unidades', NULL),
(20, NULL, 'Termica Tetrapolar 10A Curva C 4.5KA', NULL, 5430.95, 100.00, 3, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(21, NULL, 'Termica Tetrapolar 16A Curva C 4.5KA', NULL, 5430.95, 100.00, 3, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(22, NULL, 'Termica Tetrapolar 20A Curva C 4.5KA', NULL, 5430.95, 100.00, 3, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(23, NULL, 'Termica Tetrapolar 32A Curva C 4.5KA', NULL, 5430.95, 100.00, 3, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(24, NULL, 'Termica Tetrapolar 40A Curva C 4.5KA', NULL, 5988.53, 100.00, 3, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(25, NULL, 'Termica Tetrapolar 63A Curva C 4.5KA', NULL, 5988.53, 100.00, 3, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(26, NULL, 'Termica Bipolar 25A Curva C 4.5KA', NULL, 2713.61, 100.00, 6, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(27, NULL, 'Termica Bipolar 16A Curva C 4.5KA', NULL, 2713.61, 100.00, 6, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(28, NULL, 'Termica Bipolar 10A Curva C 4.5KA', NULL, 2713.61, 100.00, 6, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(29, NULL, 'Termica Bipolar 32A Curva C 4.5KA', NULL, 2766.58, 100.00, 6, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(30, NULL, 'Termica Bipolar 40A Curva C 4.5KA', NULL, 2996.12, 100.00, 6, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(31, NULL, 'Termica Bipolar 50A Curva C 4.5KA', NULL, 2996.12, 100.00, 6, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(32, NULL, 'Termica Bipolar 63A Curva C 4.5KA', NULL, 2996.12, 100.00, 6, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(33, NULL, 'Disyuntor Tetrapolar 25A 30MA AC', NULL, 18106.87, 100.00, 3, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(34, NULL, 'Disyuntor Tetrapolar 40A 30MA AC', NULL, 18106.87, 100.00, 3, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL),
(35, NULL, 'Disyuntor Tetrapolar 63A 30MA AC', NULL, 18106.87, 100.00, 3, 2, 1, NULL, NULL, 'JELUZ', 'unidades', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Proveedor`
--

CREATE TABLE `Proveedor` (
  `id_proveedor` int(11) NOT NULL,
  `razon_social` varchar(150) NOT NULL,
  `cuit` varchar(20) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Proveedor`
--

INSERT INTO `Proveedor` (`id_proveedor`, `razon_social`, `cuit`, `telefono`, `email`, `direccion`, `fecha_registro`) VALUES
(1, 'Proveedor ABC S.A.', '30-12345678-9', '555-5678', 'contacto@proveedorabc.com', 'Av. Siempre Viva 742', '2025-08-10 15:24:20'),
(2, 'Facu', '20-36814677-4', '2616511624', 'facurozalez@gmail.com', 'Aeronáutica Argentina', '2025-08-14 13:16:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Tipo_IVA`
--

CREATE TABLE `Tipo_IVA` (
  `id_iva` int(11) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  `porcentaje` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Tipo_IVA`
--

INSERT INTO `Tipo_IVA` (`id_iva`, `descripcion`, `porcentaje`) VALUES
(1, 'IVA General', 21.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Unidad_Medida`
--

CREATE TABLE `Unidad_Medida` (
  `id_unidad` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `abreviatura` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Unidad_Medida`
--

INSERT INTO `Unidad_Medida` (`id_unidad`, `nombre`, `abreviatura`) VALUES
(1, 'Unidad', 'ud');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Usuario`
--

CREATE TABLE `Usuario` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `rol` enum('Administrador','Vendedor','Cajero') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Usuario`
--

INSERT INTO `Usuario` (`id_usuario`, `nombre`, `apellido`, `usuario`, `contraseña`, `rol`) VALUES
(1, 'Admin', 'Principal', 'admin', '$2y$10$e0NRGnlxxYyq7Ny1RwT3UetT0NmHFLf7Szy.bO99JZQ.F6NcLBWJG', 'Administrador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Venta`
--

CREATE TABLE `Venta` (
  `id_venta` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `id_cliente` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Venta`
--

INSERT INTO `Venta` (`id_venta`, `fecha`, `id_cliente`, `id_usuario`, `total`) VALUES
(1, '2025-08-10 15:24:20', 1, 1, 150.00),
(7, '2025-08-19 23:53:13', 1, 1, 0.00),
(8, '2025-08-19 23:55:47', 1, 1, 0.00),
(9, '2025-08-19 23:58:43', 1, 1, 0.00),
(10, '2025-08-20 00:05:53', 1, 1, 0.00);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `Caja_Diaria`
--
ALTER TABLE `Caja_Diaria`
  ADD PRIMARY KEY (`id_caja`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `Categoria_Producto`
--
ALTER TABLE `Categoria_Producto`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `Cliente`
--
ALTER TABLE `Cliente`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `cuit` (`cuit`) USING BTREE;

--
-- Indices de la tabla `Compra`
--
ALTER TABLE `Compra`
  ADD PRIMARY KEY (`id_compra`),
  ADD KEY `id_proveedor` (`id_proveedor`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `Comprobante`
--
ALTER TABLE `Comprobante`
  ADD PRIMARY KEY (`id_comprobante`),
  ADD KEY `id_venta` (`id_venta`);

--
-- Indices de la tabla `Detalle_Compra`
--
ALTER TABLE `Detalle_Compra`
  ADD PRIMARY KEY (`id_detalle_compra`),
  ADD KEY `id_compra` (`id_compra`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `Detalle_Comprobante`
--
ALTER TABLE `Detalle_Comprobante`
  ADD PRIMARY KEY (`id_detalle_comprobante`),
  ADD KEY `id_comprobante` (`id_comprobante`),
  ADD KEY `fk_detallecomprobante_tipoiva` (`id_iva`);

--
-- Indices de la tabla `Detalle_Venta`
--
ALTER TABLE `Detalle_Venta`
  ADD PRIMARY KEY (`id_detalle_venta`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `Inventario_Movimientos`
--
ALTER TABLE `Inventario_Movimientos`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `Marca`
--
ALTER TABLE `Marca`
  ADD PRIMARY KEY (`id_marca`);

--
-- Indices de la tabla `Medio_Pago`
--
ALTER TABLE `Medio_Pago`
  ADD PRIMARY KEY (`id_medio_pago`);

--
-- Indices de la tabla `Pago_Venta`
--
ALTER TABLE `Pago_Venta`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_medio_pago` (`id_medio_pago`);

--
-- Indices de la tabla `Producto`
--
ALTER TABLE `Producto`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `id_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `Proveedor`
--
ALTER TABLE `Proveedor`
  ADD PRIMARY KEY (`id_proveedor`),
  ADD UNIQUE KEY `cuit` (`cuit`);

--
-- Indices de la tabla `Tipo_IVA`
--
ALTER TABLE `Tipo_IVA`
  ADD PRIMARY KEY (`id_iva`);

--
-- Indices de la tabla `Unidad_Medida`
--
ALTER TABLE `Unidad_Medida`
  ADD PRIMARY KEY (`id_unidad`);

--
-- Indices de la tabla `Usuario`
--
ALTER TABLE `Usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `Venta`
--
ALTER TABLE `Venta`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `Caja_Diaria`
--
ALTER TABLE `Caja_Diaria`
  MODIFY `id_caja` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `Categoria_Producto`
--
ALTER TABLE `Categoria_Producto`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `Cliente`
--
ALTER TABLE `Cliente`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `Compra`
--
ALTER TABLE `Compra`
  MODIFY `id_compra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `Comprobante`
--
ALTER TABLE `Comprobante`
  MODIFY `id_comprobante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `Detalle_Compra`
--
ALTER TABLE `Detalle_Compra`
  MODIFY `id_detalle_compra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `Detalle_Comprobante`
--
ALTER TABLE `Detalle_Comprobante`
  MODIFY `id_detalle_comprobante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `Detalle_Venta`
--
ALTER TABLE `Detalle_Venta`
  MODIFY `id_detalle_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `Inventario_Movimientos`
--
ALTER TABLE `Inventario_Movimientos`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `Marca`
--
ALTER TABLE `Marca`
  MODIFY `id_marca` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `Medio_Pago`
--
ALTER TABLE `Medio_Pago`
  MODIFY `id_medio_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `Pago_Venta`
--
ALTER TABLE `Pago_Venta`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `Producto`
--
ALTER TABLE `Producto`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `Proveedor`
--
ALTER TABLE `Proveedor`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `Tipo_IVA`
--
ALTER TABLE `Tipo_IVA`
  MODIFY `id_iva` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `Unidad_Medida`
--
ALTER TABLE `Unidad_Medida`
  MODIFY `id_unidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `Usuario`
--
ALTER TABLE `Usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `Venta`
--
ALTER TABLE `Venta`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `Caja_Diaria`
--
ALTER TABLE `Caja_Diaria`
  ADD CONSTRAINT `Caja_Diaria_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `Usuario` (`id_usuario`);

--
-- Filtros para la tabla `Compra`
--
ALTER TABLE `Compra`
  ADD CONSTRAINT `Compra_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `Proveedor` (`id_proveedor`),
  ADD CONSTRAINT `Compra_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `Usuario` (`id_usuario`);

--
-- Filtros para la tabla `Comprobante`
--
ALTER TABLE `Comprobante`
  ADD CONSTRAINT `Comprobante_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `Venta` (`id_venta`);

--
-- Filtros para la tabla `Detalle_Compra`
--
ALTER TABLE `Detalle_Compra`
  ADD CONSTRAINT `Detalle_Compra_ibfk_1` FOREIGN KEY (`id_compra`) REFERENCES `Compra` (`id_compra`) ON DELETE CASCADE,
  ADD CONSTRAINT `Detalle_Compra_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `Producto` (`id_producto`);

--
-- Filtros para la tabla `Detalle_Comprobante`
--
ALTER TABLE `Detalle_Comprobante`
  ADD CONSTRAINT `Detalle_Comprobante_ibfk_1` FOREIGN KEY (`id_comprobante`) REFERENCES `Comprobante` (`id_comprobante`),
  ADD CONSTRAINT `fk_detallecomprobante_tipoiva` FOREIGN KEY (`id_iva`) REFERENCES `Tipo_IVA` (`id_iva`);

--
-- Filtros para la tabla `Detalle_Venta`
--
ALTER TABLE `Detalle_Venta`
  ADD CONSTRAINT `Detalle_Venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `Venta` (`id_venta`) ON DELETE CASCADE,
  ADD CONSTRAINT `Detalle_Venta_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `Producto` (`id_producto`);

--
-- Filtros para la tabla `Inventario_Movimientos`
--
ALTER TABLE `Inventario_Movimientos`
  ADD CONSTRAINT `Inventario_Movimientos_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `Producto` (`id_producto`);

--
-- Filtros para la tabla `Pago_Venta`
--
ALTER TABLE `Pago_Venta`
  ADD CONSTRAINT `Pago_Venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `Venta` (`id_venta`),
  ADD CONSTRAINT `Pago_Venta_ibfk_2` FOREIGN KEY (`id_medio_pago`) REFERENCES `Medio_Pago` (`id_medio_pago`);

--
-- Filtros para la tabla `Venta`
--
ALTER TABLE `Venta`
  ADD CONSTRAINT `Venta_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `Cliente` (`id_cliente`),
  ADD CONSTRAINT `Venta_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `Usuario` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
