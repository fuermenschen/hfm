<?php

namespace App\Components;

use App\Mail\GenericMailMessage;
use App\Models\AssociationMember;
use Exception;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AdminAssociationMemberMessage extends Component
{
    use WithFileUploads;

    public ?AssociationMember $member = null;

    #[Validate('required', message: 'Wir benötigen einen Betreff.')]
    #[Validate('string', message: 'Der Betreff muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Betreff darf maximal 255 Zeichen lang sein.')]
    public ?string $subject = null;

    #[Validate('required', message: 'Wir benötigen eine Nachricht.')]
    #[Validate('string', message: 'Die Nachricht muss ein Text sein.')]
    public ?string $message = null;

    #[Validate('nullable')]
    public array $attachments = [];

    #[Validate('nullable')]
    #[Validate('file')]
    #[Validate('max:5120', message: 'Die Datei darf maximal 5 MB groß sein.')]
    public ?TemporaryUploadedFile $currentAttachment = null;

    public function render()
    {
        return view('components.admin.association-member-message');
    }

    #[On('livewire-upload-finish')]
    public function addAttachment(): void
    {
        $this->validate(
            [
                'currentAttachment' => 'file|max:5120', // 5MB Max
            ]
        );

        if ($this->currentAttachment) {

            try {
                $originalName = $this->currentAttachment->getClientOriginalName();
                $this->attachments[] = [
                    'disk' => config('filesystems.default'),
                    'name' => $originalName,
                    'path' => $this->currentAttachment->store('attachments'),
                    'mime' => $this->currentAttachment->getMimeType(),
                    'options' => [
                        'visibility' => 'private',
                    ],
                ];
            } finally {
                $this->currentAttachment = null;
            }
        }
    }

    public function removeAttachment(string $name): void
    {
        // delete the file from the disk
        $attachment = collect($this->attachments)->firstWhere('name', $name);
        if ($attachment) {
            $disk = config('filesystems.default');
            $disk = app('filesystem')->disk($disk);
            $disk->delete($attachment['path']);
        }

        // remove the attachment from the array
        $this->attachments = array_filter($this->attachments, fn ($attachment) => $attachment['name'] !== $name);
    }

    public function sendMessage(): void
    {
        $this->validate();

        if (! $this->member) {
            Flux::toast(text: 'Mitglied unbekannt.', heading: 'Fehler', variant: 'danger');

            return;
        }

        try {
            // replace placeholders
            $this->subject = $this->insertPlaceholders($this->subject);
            $this->message = $this->insertPlaceholders($this->message);

            // remove dangerous html tags
            $this->message = clean($this->message);

            // send a message
            Mail::to($this->member)->send(new GenericMailMessage(
                subject: $this->subject,
                html: $this->message,
                diskAttachments: $this->attachments
            ));

            // close and reset everything
            Flux::modal('association-member-message-editor')->close();
            $this->reset();
        } catch (Exception $e) {

            Flux::toast(text: 'Fehler beim Senden der Nachricht', heading: 'Fehler', variant: 'danger');

            return;
        }

        Flux::toast(text: 'Nachricht erfolgreich gesendet.', heading: 'Erfolg', variant: 'success');

    }

    #[On('sendMail')]
    public function openAndPrepareSendMailModal($member_id): void
    {
        // make sure to reset everything
        $this->reset();

        $this->member = AssociationMember::find($member_id);

        if (! $this->member) {

            Flux::toast(text: 'Mitglied unbekannt.', heading: 'Fehler', variant: 'danger');

            return;
        }
        $this->subject = 'Hey [!first_name], es gibt News!';
        $this->message = '<p>Hallo [!first_name]</p><p><i>NACHRICHT HIER</i></p><p>Herzliche Grüsse<br>'.auth()->user()->name.'</p>';

        Flux::modal('association-member-message-editor')->show();

    }

    public function getMemberAttributesProperty()
    {
        return (new AssociationMember)->getFillable();
    }

    public function insertPlaceholders(string $text): string
    {
        $modelProperties = array_keys($this->member->getAttributes());

        $placeholders = [];
        foreach ($modelProperties as $property) {
            $placeholders['[!'.$property.']'] = $this->member->{$property};
        }

        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }
}
