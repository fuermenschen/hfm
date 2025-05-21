<?php

namespace App\Components;

use App\Mail\GenericMailMessage;
use App\Models\AssociationMember;
use Exception;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AdminAssociationMemberMessage extends Component
{
    use WithFileUploads;

    /**
     * @var Collection<int, AssociationMember>
     */
    public Collection $all_members;

    #[Validate('array')]
    #[Validate('min:1', message: 'Bitte wähle mindestens ein Mitglied aus.')]
    #[Validate('exists:association_members,id', message: 'Die Mitglieder existieren nicht.')]
    public array $selected_members = [];

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

    public function mount(): void
    {
        // get all members from the database
        $this->all_members = AssociationMember::all(['id', 'first_name', 'last_name', 'email'])->sortBy('first_name');
    }

    public function render()
    {
        return view('components.admin.association-member-message',
            [
                'all_members' => $this->all_members,
            ]);
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

        if ($this->selected_members === []) {
            Flux::toast(text: 'Keine Mitglieder gewählt!', heading: 'Fehler', variant: 'danger');

            return;
        }

        try {

            foreach ($this->selected_members as $member_id) {

                $member = $this->all_members->firstWhere('id', $member_id);

                // check if the member is valid
                if (! $member instanceof AssociationMember) {
                    continue;
                }

                $subject = $this->insertPlaceholders($this->subject, $member);
                $message = $this->insertPlaceholders($this->message, $member);

                // remove dangerous html tags
                $subject = clean($subject);
                $message = clean($message);

                // send a message
                Mail::to($member)->send(new GenericMailMessage(
                    subject: $subject,
                    html: $message,
                    diskAttachments: $this->attachments
                ));

            }

            // close and reset everything
            Flux::modal('association-member-message-editor')->close();
            $this->reset();
            $this->mount();
        } catch (Exception $e) {
            Flux::toast(text: 'Fehler beim Senden der Nachricht', heading: 'Fehler', variant: 'danger');

            return;
        }

        Flux::toast(text: 'Nachricht erfolgreich gesendet.', heading: 'Erfolg', variant: 'success');
    }

    #[On('openMemberMessageModal')]
    public function openAndPrepareSendMailModal($member_ids): void
    {
        $this->selected_members = $member_ids;

        $this->subject = 'Hey [!first_name], es gibt News!';
        $this->message = '<p>Hallo [!first_name]</p><p><i>NACHRICHT HIER</i></p><p>Herzliche Grüsse<br>'.auth()->user()->name.'</p>';

        Flux::modal('association-member-message-editor')->show();
    }

    public function getMemberAttributesProperty()
    {
        return (new AssociationMember)->getFillable();
    }

    /**
     * Insert placeholders for a given member.
     * In the future, this can be extended to handle multiple members.
     */
    public function insertPlaceholders(string $text, ?AssociationMember $member = null): string
    {
        $member = $member ?? $this->members->first();

        if (! $member) {
            return $text;
        }

        $modelProperties = array_keys($member->getAttributes());

        $placeholders = [];
        foreach ($modelProperties as $property) {
            $placeholders['[!'.$property.']'] = $member->{$property};
        }

        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }
}
