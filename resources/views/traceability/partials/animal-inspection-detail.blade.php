@php
    $anteMortem = $animal['ante_mortem'] ?? [];
    $postMortem = $animal['post_mortem'] ?? null;
@endphp

<div class="animal-inspection-detail">
  @if (!empty($anteMortem))
    <p class="animal-inspection-title">{{ __('Ante-mortem inspection') }}</p>
    @foreach ($anteMortem as $am)
      <div class="animal-inspection-block">
        <p class="animal-inspection-meta">
          {{ $am['inspection_date'] }}
          · {{ __('Outcome') }}: <strong>{{ $am['outcome'] }}</strong>
          @if (!empty($am['inspector'])) · {{ __('Inspector') }}: {{ $am['inspector'] }} @endif
        </p>
        @if (!empty($am['outcome_notes']))
          <p class="animal-inspection-notes">{{ $am['outcome_notes'] }}</p>
        @endif
        @if (!empty($am['rows']))
          <table class="checklist" role="presentation">
            <thead>
              <tr>
                <th>{{ __('Item') }}</th>
                <th>{{ __('Result') }}</th>
                <th>{{ __('Notes') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($am['rows'] as $row)
                <tr>
                  <td>{{ $row['label'] }}</td>
                  <td>{{ $row['value'] }}</td>
                  <td>{{ $row['notes'] ?: '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <p class="animal-inspection-empty">{{ __('No checklist items recorded for this animal.') }}</p>
        @endif
      </div>
    @endforeach
  @else
    <p class="animal-inspection-empty">{{ __('No ante-mortem inspection recorded for this animal.') }}</p>
  @endif

  @if ($postMortem)
    <p class="animal-inspection-title">{{ __('Post-mortem inspection') }}</p>
    <div class="animal-inspection-block">
      <p class="animal-inspection-meta">
        {{ $postMortem['inspection_date'] }}
        @if (!empty($postMortem['outcome']))
          · {{ __('Outcome') }}: <strong>{{ $postMortem['outcome'] }}</strong>
        @endif
        @if (!empty($postMortem['inspector'])) · {{ __('Inspector') }}: {{ $postMortem['inspector'] }} @endif
      </p>
      @if (!empty($postMortem['outcome_notes']))
        <p class="animal-inspection-notes">{{ $postMortem['outcome_notes'] }}</p>
      @endif
      @if (!empty($postMortem['carcass_rows']))
        <p class="animal-inspection-subtitle">{{ __('Carcass inspection') }}</p>
        <table class="checklist" role="presentation">
          <thead>
            <tr>
              <th>{{ __('Item') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Notes') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($postMortem['carcass_rows'] as $row)
              <tr>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['value'] }}</td>
                <td>{{ $row['notes'] ?: '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
      @if (!empty($postMortem['organ_rows']))
        <p class="animal-inspection-subtitle">{{ __('Organ inspection') }}</p>
        <table class="checklist" role="presentation">
          <thead>
            <tr>
              <th>{{ __('Item') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Notes') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($postMortem['organ_rows'] as $row)
              <tr>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['value'] }}</td>
                <td>{{ $row['notes'] ?: '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
      @if (empty($postMortem['carcass_rows']) && empty($postMortem['organ_rows']) && empty($postMortem['outcome']))
        <p class="animal-inspection-empty">{{ __('No post-mortem checklist recorded for this animal.') }}</p>
      @endif
    </div>
  @else
    <p class="animal-inspection-title">{{ __('Post-mortem inspection') }}</p>
    <p class="animal-inspection-empty">{{ __('No post-mortem inspection recorded for this animal.') }}</p>
  @endif
</div>
