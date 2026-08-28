<?php

use App\Models\DonationEvent;
use App\Models\Faq;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('hides the detach warning and keeps remaining row controls enabled after saving a removed faq assignment', function (): void {
    actingAs(User::factory()->create());

    $event = DonationEvent::factory()->create();
    $alpha = Faq::factory()->create(['title' => 'Alpha FAQ']);
    $beta = Faq::factory()->create(['title' => 'Beta FAQ']);
    $gamma = Faq::factory()->create(['title' => 'Gamma FAQ']);

    foreach ([$alpha, $beta, $gamma] as $faq) {
        $faq->donationEvents()->attach($event->id, ['group' => 'general', 'sort_order' => 10, 'is_published' => true]);
    }

    $visibleWarnings = <<<'JS'
        (() => Array.from(document.querySelectorAll("*"))
          .filter(el => el.children.length === 0 && el.textContent.trim() === "Vom Anlass entfernen" && el.offsetParent !== null)
          .map(el => {
            let row = el;
            while (row && !(typeof row.className === "string" && row.className.includes("border-t") && row.className.includes("grid"))) row = row.parentElement;
            return row ? row.textContent.trim() : "UNKNOWN ROW";
          }))()
        JS;

    $page = visit('/admin/anlaesse/'.$event->id.'/bearbeiten')
        ->click('internal:role=tab[name="FAQs"]')
        ->assertSee('Zugeordnete FAQs');

    $page->script(<<<'JS'
        (() => {
          const target = Array.from(document.querySelectorAll("[role=switch]"))
            .find(s => (s.closest(".space-y-3")?.textContent ?? "").includes("Alpha FAQ"));
          target?.click();
        })()
        JS);
    $page->wait(1);

    expect($page->script($visibleWarnings))->each->toContain('Alpha FAQ');

    $page->press('FAQs speichern')->wait(2);

    expect($page->script($visibleWarnings))->toBe([]);

    $attachedSwitchStates = <<<'JS'
        (() => ["Alpha FAQ", "Beta FAQ", "Gamma FAQ"].map((title) => {
          const row = Array.from(document.querySelectorAll("[role=switch]"))
            .map((s) => {
              let el = s;
              while (el && !(typeof el.className === "string" && el.className.includes("border-t") && el.className.includes("grid"))) el = el.parentElement;
              return el;
            })
            .find((row) => row && row.textContent.includes(title));
          if (! row) return title + ": UNASSIGNED";
          const switches = Array.from(row.querySelectorAll("[role=switch]"));
          if (switches.length === 0) return title + ": NO SWITCHES";
          const anyDisabled = switches.some((s) => s.hasAttribute("disabled"));
          return title + ": " + (anyDisabled ? "ANY_DISABLED" : "ALL_ENABLED");
        }))()
        JS;

    expect($page->script($attachedSwitchStates))->toBe([
        'Alpha FAQ: UNASSIGNED',
        'Beta FAQ: ALL_ENABLED',
        'Gamma FAQ: ALL_ENABLED',
    ]);
});
