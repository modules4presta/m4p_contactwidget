<?php

/**
 * m4p_contactwidget
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Opening hours of the contact desk: one from–to range per weekday (or closed) and dated exceptions,
 * read in the shop's time zone.
 */
class M4pContactSchedule
{
    /** Days ahead searched for the next opening and listed for the browser. */
    public const HORIZON_DAYS = 14;

    /** @var array<int, array{0: string, 1: string}|null> ISO weekday (1 = Monday) => [from, to] or null */
    private $week;

    /** @var array<string, array{0: string, 1: string}|null> Y-m-d => [from, to] or null (closed) */
    private $exceptions;

    /** @var DateTimeZone */
    private $timezone;

    /**
     * @param array<int, array{0: string, 1: string}|null> $week
     * @param array<string, array{0: string, 1: string}|null> $exceptions
     */
    public function __construct(array $week, array $exceptions, DateTimeZone $timezone)
    {
        $this->week = $week;
        $this->exceptions = $exceptions;
        $this->timezone = $timezone;
    }

    public static function fromConfiguration(): self
    {
        $timezone = new DateTimeZone((string) Configuration::get('PS_TIMEZONE') ?: date_default_timezone_get());

        return new self(
            self::parseWeek(M4p_ContactWidget::scheduleJson()),
            self::parseExceptions((string) Configuration::get(M4p_ContactWidget::CFG_EXCEPTIONS)),
            $timezone
        );
    }

    /**
     * @return array<int, array{0: string, 1: string}|null>
     */
    public static function parseWeek(string $json): array
    {
        $stored = json_decode($json, true);
        $week = [];
        foreach (range(1, 7) as $day) {
            $range = is_array($stored) ? ($stored[$day] ?? $stored[(string) $day] ?? null) : null;
            $week[$day] = is_array($range) && self::isRange($range[0] ?? '', $range[1] ?? '') ? [(string) $range[0], (string) $range[1]] : null;
        }

        return $week;
    }

    /**
     * One exception per line: `2026-12-24 8:00-12:00` or `2026-12-25 closed` (any word other than a range
     * closes the day). Lines that do not start with a date are skipped.
     *
     * @return array<string, array{0: string, 1: string}|null>
     */
    public static function parseExceptions(string $text): array
    {
        $exceptions = [];
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            if (!preg_match('/^\s*(\d{4}-\d{2}-\d{2})\s*(.*)$/', $line, $match) || !strtotime($match[1])) {
                continue;
            }
            $exceptions[$match[1]] = preg_match('/^(\d{1,2}:\d{2})\s*[-–]\s*(\d{1,2}:\d{2})/u', trim($match[2]), $range) && self::isRange($range[1], $range[2])
                ? [self::normalizeTime($range[1]), self::normalizeTime($range[2])]
                : null;
        }

        return $exceptions;
    }

    public static function isRange(string $from, string $to): bool
    {
        return self::isTime($from) && self::isTime($to) && self::normalizeTime($from) < self::normalizeTime($to);
    }

    public static function isTime(string $value): bool
    {
        return (bool) preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', trim($value));
    }

    public static function normalizeTime(string $value): string
    {
        [$hours, $minutes] = explode(':', trim($value));

        return sprintf('%02d:%s', (int) $hours, $minutes);
    }

    public function isConfigured(): bool
    {
        return array_filter($this->week) !== [] || $this->exceptions !== [];
    }

    public function timezone(): DateTimeZone
    {
        return $this->timezone;
    }

    /**
     * @return array<int, array{0: string, 1: string}|null>
     */
    public function week(): array
    {
        return $this->week;
    }

    /**
     * The range of one date, exceptions first.
     *
     * @return array{0: string, 1: string}|null
     */
    public function rangeOn(DateTimeImmutable $day): ?array
    {
        $key = $day->format('Y-m-d');
        if (array_key_exists($key, $this->exceptions)) {
            return $this->exceptions[$key];
        }

        return $this->week[(int) $day->format('N')];
    }

    /**
     * Opening periods from today on, as Unix timestamps with the date they belong to.
     *
     * @return array<int, array{date: string, start: int, end: int}>
     */
    public function periods(DateTimeImmutable $now): array
    {
        $today = $now->setTimezone($this->timezone)->setTime(0, 0);
        $periods = [];
        foreach (range(0, self::HORIZON_DAYS) as $offset) {
            $day = $today->modify('+' . $offset . ' day');
            $range = $this->rangeOn($day);
            if ($range === null) {
                continue;
            }
            $periods[] = [
                'date' => $day->format('Y-m-d'),
                'start' => $this->at($day, $range[0])->getTimestamp(),
                'end' => $this->at($day, $range[1])->getTimestamp(),
            ];
        }

        return $periods;
    }

    /**
     * Exceptions from today until the horizon, for the list in the panel.
     *
     * @return array<string, array{0: string, 1: string}|null>
     */
    public function upcomingExceptions(DateTimeImmutable $now): array
    {
        $from = $now->setTimezone($this->timezone)->format('Y-m-d');
        $until = $now->setTimezone($this->timezone)->modify('+' . self::HORIZON_DAYS . ' day')->format('Y-m-d');
        $upcoming = array_filter($this->exceptions, static function (string $date) use ($from, $until): bool {
            return $date >= $from && $date <= $until;
        }, ARRAY_FILTER_USE_KEY);
        ksort($upcoming);

        return $upcoming;
    }

    private function at(DateTimeImmutable $day, string $time): DateTimeImmutable
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $day->setTime($hours, $minutes);
    }
}
