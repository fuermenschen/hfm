<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StoryImageVariant;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\Partner;
use App\Support\AdminFiles\AdminFileStorage;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class AthleteStoryImageService
{
    private const int CENTER_X = 540;

    private const int TITLE_MAX_WIDTH = 920;

    public function __construct(private readonly AdminFileStorage $adminFileStorage) {}

    public function authorizePortalAccess(AthleteRegistration $registration): void
    {
        $registration->loadMissing('donationEvent');

        $externalUserId = auth('external')->id();
        $isAthlete = $registration->external_user_id === $externalUserId;
        $isDonor = $registration->donations()
            ->where('donor_external_user_id', $externalUserId)
            ->exists();

        abort_unless(
            ($isAthlete || $isDonor)
                && $registration->verified === true
                && $registration->donationEvent->is_published === true,
            404,
        );
    }

    /**
     * @return array{contents:string,filename:string}
     */
    public function build(AthleteRegistration $registration, StoryImageVariant $variant): array
    {
        $registration->loadMissing(['donationEvent', 'externalUser']);

        $event = $registration->donationEvent;
        $athlete = $registration->externalUser;
        $filename = sprintf(
            'story_single_%s_%s.jpg',
            $variant->value,
            $athlete->public_id_string,
        );
        $manager = new ImageManager(Driver::class);
        $image = $manager->createImage(1080, 1920)->fill($variant->backgroundColor());
        $font = resource_path('fonts/darkmode_on_');

        $logo = $this->decodeSvg($manager, $variant->logoPath())
            ->fillTransparentAreas($variant->backgroundColor())
            ->scaleDown(width: 550);
        $image->insert($logo, 265, 115);

        $this->drawText($image, $this->formatEventDate($event->starts_at, $event->location_city), $variant->textColor(), $font.'light.otf', 42, self::CENTER_X, 520);
        // Scale long titles to keep them clear of following copy.
        ['lines' => $titleLines, 'fontSize' => $titleFontSize] = $this->layoutTitle($event->title, $font.'xbold.otf');
        $titleLineHeight = (int) ($titleFontSize * 1.2);
        foreach ($titleLines as $line => $titleLine) {
            $this->drawText($image, $titleLine, $variant->textColor(), $font.'xbold.otf', $titleFontSize, self::CENTER_X, 650 + ($line * $titleLineHeight));
        }

        $this->drawText($image, 'Unterstützt du mich', $variant->textColor(), $font.'light.otf', 80, self::CENTER_X, 947);
        $this->drawText($image, 'mit einer Spende?', $variant->textColor(), $font.'light.otf', 80, self::CENTER_X, 1042);
        $this->drawText($image, '1.', $variant->textColor(), $font.'medium.otf', 45, 245, 1200, 'left');
        $this->drawText($image, 'Gehe auf', $variant->textColor(), $font.'light.otf', 45, 350, 1200, 'left');
        $this->drawText($image, $this->appHost(), $variant->textColor(), $font.'light.otf', 45, 350, 1270, 'left');
        $this->drawText($image, '2.', $variant->textColor(), $font.'medium.otf', 45, 245, 1370, 'left');
        $this->drawText($image, 'Wähle in der Liste', $variant->textColor(), $font.'light.otf', 45, 350, 1370, 'left');
        $this->drawText($image, 'meinen Namen aus:', $variant->textColor(), $font.'light.otf', 45, 350, 1440, 'left');
        $this->drawRoundedBar($image);
        $this->drawText(
            $image,
            $athlete->privacy_name.' ('.$athlete->public_id_string.')',
            '#f8fafc',
            $font.'medium.otf',
            50,
            self::CENTER_X,
            1576,
        );

        foreach ($this->partnerLogos($event, $variant) as $partnerLogo) {
            $partnerLogoImage = $this->decodeSvg($manager, $partnerLogo['path'])
                ->fillTransparentAreas($variant->backgroundColor())
                ->scale(width: $partnerLogo['width'] - 16, height: 160);
            $image->insert(
                $partnerLogoImage,
                $partnerLogo['x'] + intdiv($partnerLogo['width'] - $partnerLogoImage->width(), 2),
                $partnerLogo['y'] + intdiv(160 - $partnerLogoImage->height(), 2),
            );
        }

        // 4:4:4 sampling keeps thin logo strokes crisp; 4:2:0 blurs them.
        $image->core()->native()->setImageProperty('jpeg:sampling-factor', '1x1');
        $contents = $image->encodeUsingFormat(Format::JPEG, quality: 95)->toString();

        return [
            'contents' => $contents,
            'filename' => $filename,
        ];
    }

    protected function drawText(
        ImageInterface $image,
        string $text,
        string $color,
        string $fontPath,
        int $fontSize,
        int $x,
        int $y,
        string $alignment = 'center',
    ): void {
        $image->text($text, $x, $y, function ($font) use ($alignment, $color, $fontPath, $fontSize): void {
            $font->file($fontPath);
            $font->size($fontSize);
            $font->color($color);
            $font->align($alignment);
        });
    }

    /**
     * Split title into one or two balanced lines (longest line as short as
     * possible, like CSS text-wrap: balance), shrinking the font until it fits.
     *
     * @return array{lines: array<int, string>, fontSize: int}
     */
    protected function layoutTitle(string $title, string $fontPath): array
    {
        $words = explode(' ', trim(preg_replace('/\s+/', ' ', $title) ?? ''));
        $fontSize = 115;

        while (true) {
            $oneLine = implode(' ', $words);
            if ($this->textWidth($oneLine, $fontPath, $fontSize) <= self::TITLE_MAX_WIDTH) {
                return ['lines' => [$oneLine], 'fontSize' => $fontSize];
            }

            if (count($words) === 1) {
                return ['lines' => [$oneLine], 'fontSize' => $fontSize];
            }

            $bestLines = null;
            $bestWidth = PHP_FLOAT_MAX;
            $counter = count($words);
            for ($i = 1; $i < $counter; $i++) {
                $lines = [implode(' ', array_slice($words, 0, $i)), implode(' ', array_slice($words, $i))];
                $width = max(
                    $this->textWidth($lines[0], $fontPath, $fontSize),
                    $this->textWidth($lines[1], $fontPath, $fontSize),
                );
                if ($width < $bestWidth) {
                    $bestWidth = $width;
                    $bestLines = $lines;
                }
            }

            if ($bestWidth <= self::TITLE_MAX_WIDTH || $fontSize <= 60) {
                return ['lines' => $bestLines, 'fontSize' => $fontSize];
            }

            $fontSize -= 5;
        }
    }

    protected function textWidth(string $text, string $fontPath, int $fontSize): float
    {
        $draw = new \ImagickDraw;
        $draw->setFont($fontPath);
        $draw->setFontSize($fontSize);

        return (new \Imagick)->queryFontMetrics($draw, $text)['textWidth'];
    }

    protected function appHost(): string
    {
        return parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'hfm-winti.ch';
    }

    protected function formatEventDate(?CarbonInterface $date, ?string $city): string
    {
        $formattedDate = $date?->locale('de_CH')->translatedFormat('d. F Y') ?? '';

        return $city !== null && $city !== '' ? $formattedDate.' in '.$city : $formattedDate;
    }

    /**
     * @return array<int, array{path:string,x:int,y:int,width:int}>
     */
    protected function partnerLogos(DonationEvent $event, StoryImageVariant $variant): array
    {
        $partners = $event->partners()
            ->wherePivot('is_published', true)
            ->orderByPivot('sort_order')
            ->get();

        $count = $partners->count();
        if ($count === 0) {
            return [];
        }

        $rowPadding = 64;
        $gap = 64;
        $slotWidth = intdiv(1080 - (2 * $rowPadding) - (($count - 1) * $gap), $count);
        $startX = $rowPadding;

        return $partners
            ->values()
            ->map(function (Partner $partner, int $index) use ($gap, $slotWidth, $startX, $variant): ?array {
                $filename = $variant === StoryImageVariant::Light
                    ? $partner->logo_light_filename
                    : $partner->logo_dark_filename;
                $relativePath = $this->adminFileStorage->normalizePath('partners/'.$filename);
                $disk = Storage::disk('public');

                if (! $disk->exists($relativePath)) {
                    return null;
                }

                return [
                    'path' => $disk->path($relativePath),
                    'x' => $startX + ($index * ($slotWidth + $gap)),
                    'y' => 1696,
                    'width' => $slotWidth,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function drawRoundedBar(ImageInterface $image): void
    {
        $image->drawRectangle(function ($rectangle): void {
            $rectangle->at(120, 1500)->size(840, 120)->background('#e81010');
        });
        $image->drawRectangle(function ($rectangle): void {
            $rectangle->at(85, 1535)->size(910, 50)->background('#e81010');
        });

        foreach ([
            [120, 1535],
            [960, 1535],
            [120, 1585],
            [960, 1585],
        ] as [$x, $y]) {
            $image->drawCircle(function ($circle) use ($x, $y): void {
                $circle->at($x, $y)->radius(35)->background('#e81010');
            });
        }
    }

    protected function decodeSvg(ImageManager $manager, string $path): ImageInterface
    {
        $svg = new \Imagick;
        $svg->setResolution(300, 300);
        $svg->setBackgroundColor(new \ImagickPixel('transparent'));
        $svg->readImage($path);
        $svg->setIteratorIndex(0);
        $svg->setImageFormat('png');
        $svg->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);

        return $manager->decode($svg->getImagesBlob());
    }
}
