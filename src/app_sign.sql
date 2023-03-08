/*
 Target Server Type    : MySQL
 Target Server Version : 80018
 File Encoding         : 65001
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for app_sign
-- ----------------------------
DROP TABLE IF EXISTS `app_sign`;
CREATE TABLE `app_sign`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `app_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '应用id',
  `app_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '应用秘钥',
  `app_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '应用名称',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0=禁用，1=启用',
  `encrypt_body` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'body报文是否加密传输：0=禁用，1=启用	',
  `rsa_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'rsa状态：0=禁用，1=启用',
  `private_key` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '私钥',
  `public_key` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '公钥',
  `expired_at` datetime(0) NULL DEFAULT NULL COMMENT '过期时间',
  `created_at` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '应用签名表' ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
