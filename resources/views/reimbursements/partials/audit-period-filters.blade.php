@php
    $filterPrefix = $filterPrefix ?? 'audit';
    $mainWeekLabel = request('tab', 'management') === 'payment' ? 'Semana de pago' : 'Semana fiscal';
@endphp

<div class="col-span-1 md:col-span-2">
    <label for="{{ $filterPrefix }}_from_week" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $mainWeekLabel }} desde</label>
    <select name="from_week" id="{{ $filterPrefix }}_from_week" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        <option value="">Cualquiera</option>
        @foreach($availableWeeks as $availableWeek)
            <option value="{{ $availableWeek }}" @selected(request('from_week') === $availableWeek)>Semana {{ $availableWeek }}</option>
        @endforeach
    </select>
</div>

<div class="col-span-1 md:col-span-2">
    <label for="{{ $filterPrefix }}_to_week" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $mainWeekLabel }} hasta</label>
    <select name="to_week" id="{{ $filterPrefix }}_to_week" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        <option value="">Cualquiera</option>
        @foreach($availableWeeks as $availableWeek)
            <option value="{{ $availableWeek }}" @selected(request('to_week') === $availableWeek)>Semana {{ $availableWeek }}</option>
        @endforeach
    </select>
</div>

<div class="col-span-1 md:col-span-2">
    <label for="{{ $filterPrefix }}_user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Usuario / beneficiario</label>
    <select name="user_id" id="{{ $filterPrefix }}_user_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        <option value="">Todos</option>
        @foreach($auditFilterUsers as $filterUser)
            <option value="{{ $filterUser->id }}" @selected((string) request('user_id') === (string) $filterUser->id)>{{ $filterUser->name }}</option>
        @endforeach
    </select>
</div>

<div class="col-span-1 md:col-span-2">
    <label for="{{ $filterPrefix }}_upload_week" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subido en semana exacta</label>
    <select name="upload_week" id="{{ $filterPrefix }}_upload_week" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        <option value="">Cualquiera</option>
        @foreach($availableUploadWeeks as $uploadWeek)
            <option value="{{ $uploadWeek }}" @selected(request('upload_week') === $uploadWeek)>Semana {{ $uploadWeek }}</option>
        @endforeach
    </select>
</div>

<div class="col-span-1 md:col-span-2">
    <label for="{{ $filterPrefix }}_upload_from_week" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subido desde semana</label>
    <select name="upload_from_week" id="{{ $filterPrefix }}_upload_from_week" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        <option value="">Cualquiera</option>
        @foreach($availableUploadWeeks as $uploadWeek)
            <option value="{{ $uploadWeek }}" @selected(request('upload_from_week') === $uploadWeek)>Semana {{ $uploadWeek }}</option>
        @endforeach
    </select>
</div>

<div class="col-span-1 md:col-span-2">
    <label for="{{ $filterPrefix }}_upload_to_week" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subido hasta semana</label>
    <select name="upload_to_week" id="{{ $filterPrefix }}_upload_to_week" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        <option value="">Cualquiera</option>
        @foreach($availableUploadWeeks as $uploadWeek)
            <option value="{{ $uploadWeek }}" @selected(request('upload_to_week') === $uploadWeek)>Semana {{ $uploadWeek }}</option>
        @endforeach
    </select>
</div>
