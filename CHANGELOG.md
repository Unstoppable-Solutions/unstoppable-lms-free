# Changelog

All notable user-facing changes are documented here. For WordPress.org users,
the same changelog lives in `readme.txt` and renders on the plugin's directory
page. This file follows the format described at https://keepachangelog.com.

## [1.7.0] - 2026-11-21

### Added
- Course progress widget for dashboard - students see their active courses at a glance
- "Continue Learning" button takes students directly to their last lesson
- Course completion emails - automatic notification when students finish
- Improved admin dashboard with quick stats (enrolled students, completion rates)
- Bulk enroll students from CSV file
- PHP 8.3 compatibility verified

### Improved
- Faster progress tracking with optimized database queries
- Better mobile responsiveness on course pages
- Cleaner lesson navigation with previous/next buttons
- Admin menu now shows unread count badges

### Fixed
- Progress not saving on some hosting configurations
- Course ordering now respects menu_order properly

### Tested
- WordPress 6.8 compatibility confirmed

## [1.6.5] - 2026-11-02

### Added
- "Preview as Student" mode - admins can view courses exactly as students see them

### Fixed
- PHP 8.1+ deprecation warning in admin menu registration (null to string)
- Fatal error from extra closing brace in admin menus
- Fatal error from License class check in free version

### Changed
- Renamed "Cohorts" to "Classes" in menu for clarity

## [1.6.1]

### Added
- Settings link on Plugins page
- Go Pro link on Plugins page

### Improved
- Menu branding color updated to gold (#f0b849)
- Full compliance with WordPress plugin standards

## [1.6.0]

### Added
- Free version limits (1 course, 5 lessons)
- Upgrade prompts for Pro features
- Pro feature preview pages

### Improved
- Admin interface

## [1.5.0]

### Added
- Discussion forums (Pro)
- Cohorts / class instances (Pro)

## [1.4.0]

### Added
- Advanced gradebook system (Pro)
- Reports dashboard

## [1.0.0]

### Added
- Initial release
