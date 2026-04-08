<?php

namespace App\Modules\Document\Filament\Resources\DocumentResource\Pages;

use App\Modules\Document\Enums\DocumentVisibility;
use App\Modules\Document\Filament\Resources\DocumentResource\DocumentResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Gate;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    public function mount(): void
    {
        if (!Gate::allows('create', static::getModel())) {
            Notification::make()
                ->danger()
                ->title('Quota Terlampaui')
                ->body('Anda telah mencapai batas maksimum dokumen. Silakan upgrade paket atau hapus dokumen yang tidak diperlukan.')
                ->persistent()
                ->send();

            $this->redirect(static::getResource()::getUrl('index'), navigate: false);
            return;
        }

        parent::mount();
    }

    protected function getFormActions(): array
    {
        $actions = parent::getFormActions();

        $user = auth()->user();
        $hasAIAccess = $user->tenant && $user->tenant->hasAIGenerateAccess();

        if ($hasAIAccess) {
            $actions[] = Actions\Action::make('generate_ai')
                ->label('Generate From AI')
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Generate Content dengan AI')
                ->modalDescription('AI akan menghasilkan konten berdasarkan judul dokumen. Konten yang ada akan diganti.')
                ->modalSubmitActionLabel('Generate')
                ->action(function () {
                    $title = $this->data['title'] ?? null;

                    if (!$title) {
                        Notification::make()
                            ->warning()
                            ->title('Judul Diperlukan')
                            ->body('Silakan isi judul dokumen terlebih dahulu.')
                            ->send();
                        return;
                    }

                    try {
                        $generatedContent = $this->generateAIContent($title);

                        $this->data['content'] = $generatedContent;

                        $this->form->fill($this->data);

                        Notification::make()
                            ->success()
                            ->title('Konten Berhasil Digenerate')
                            ->body('Konten telah digenerate menggunakan AI.')
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Gagal Generate Konten')
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->send();
                    }
                });
        }

        return $actions;
    }

    protected function generateAIContent(string $title): string
    {

        $paragraphs = [
            "<p><strong>{$title}</strong></p>",
            "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>",
            "<p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>",
            "<h2>Key Points</h2>",
            "<ul>
                <li>Comprehensive analysis of {$title}</li>
                <li>Detailed insights and recommendations</li>
                <li>Best practices implementation</li>
                <li>Future considerations</li>
            </ul>",
            "<p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.</p>",
            "<h2>Conclusion</h2>",
            "<p>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.</p>"
        ];

        return implode("\n", $paragraphs);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['user_id'] = auth()->id();
        $data['owner_id'] = auth()->id();

        if (auth()->user()->isStaff() && (!isset($data['visibility']) || $data['visibility'] === DocumentVisibility::PRIVATE->value)) {
            $data['visibility'] = DocumentVisibility::PUBLIC->value;
        }

        if (!isset($data['visibility'])) {
            $data['visibility'] = auth()->user()->isStaff()
                ? DocumentVisibility::PUBLIC->value
                : DocumentVisibility::PRIVATE->value;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
