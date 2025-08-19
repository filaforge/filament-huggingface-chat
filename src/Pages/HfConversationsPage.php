<?php

namespace Filaforge\HuggingfaceChat\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filaforge\HuggingfaceChat\Models\Conversation;

class HfConversationsPage extends Page implements Tables\Contracts\HasTable
{
	use Tables\Concerns\InteractsWithTable;

	protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-table-cells';
	protected string $view = 'huggingface-chat::pages.conversations';
	protected static ?string $navigationLabel = 'HF Conversations';
	protected static \UnitEnum|string|null $navigationGroup = 'HF Chat';
	protected static ?int $navigationSort = 12;
	protected static ?string $title = 'HF Conversations';

	public function table(Table $table): Table
	{
		$query = Conversation::query();
		$userId = (int) auth()->id();
		if ($userId) {
			$query->where('user_id', $userId);
		}

		$expr = $this->jsonArrayLengthExpr('messages');
		if ($expr) {
			$query->select($query->getModel()->getTable() . '.*')->selectRaw($expr . ' as messages_count');
		}

		$query->latest('updated_at');

		return $table
			->query($query)
			->defaultPaginationPageOption(10)
			->columns([
				TextColumn::make('title')->label('Title')->searchable()->sortable()->limit(60)->wrap(false),
				TextColumn::make('model')->label('Model')->toggleable(isToggledHiddenByDefault: false)->limit(40),
				TextColumn::make('messages_count')->label('Messages')->visible((bool) $expr)->sortable(['messages_count'])->formatStateUsing(fn ($state) => (string) ($state ?? 0)),
				TextColumn::make('updated_at')->label('Updated')->dateTime()->sortable(),
			])
			->filters([
				TernaryFilter::make('has_replies')->label('Has replies')->queries(
					true: function (Builder $q) use ($expr): Builder { return $expr ? $q->whereRaw($expr . ' > 1') : $q; },
					false: function (Builder $q) use ($expr): Builder { return $expr ? $q->whereRaw($expr . ' <= 1') : $q; }
				),
			])
			->striped()
			->emptyStateHeading('No conversations found.');
	}

	private function jsonArrayLengthExpr(string $column): ?string
	{
		$driver = DB::connection()->getDriverName();
		return match ($driver) {
			'mysql' => "JSON_LENGTH($column)",
			'pgsql' => "json_array_length(($column)::json)",
			'sqlite' => "json_array_length($column)",
			default => null,
		};
	}
}


