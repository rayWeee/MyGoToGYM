-- Run once on an existing Gym Tracker database to support verified email changes.
ALTER TABLE `users`
  ADD COLUMN `pending_email` varchar(150) DEFAULT NULL AFTER `email`;