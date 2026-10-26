-- Журнал подарочных сертификатов LEOLSBEER
CREATE TABLE IF NOT EXISTS certs (
  num          INT UNSIGNED NOT NULL PRIMARY KEY,
  amount       INT UNSIGNED NOT NULL,
  name         VARCHAR(200) NOT NULL,
  format       CHAR(1)      NOT NULL DEFAULT 'h',  -- h = обычный, v = с пожеланием
  wish         TEXT         NULL,
  created_at   BIGINT       NULL,                  -- миллисекунды Unix
  redeemed_at  BIGINT       NULL                   -- NULL = активен
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Уже выданные сертификаты, чтобы нумерация продолжилась с 1431
INSERT IGNORE INTO certs (num, amount, name, format, wish, created_at, redeemed_at) VALUES
(1429, 10000, 'Александра', 'v', 'Пусть с опозданием подарок, но\nлучше поздно, чем никогда )))\n\nГлавное, чтобы в жизни никогда не\nопаздывали чудеса,\nхорошие вести и счастливые\nмоменты.\nС днем рождения ))))', NULL, NULL),
(1430, 3500, 'Жени', 'h', '', NULL, NULL);
