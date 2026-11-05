@props(['compact' => false, 'right' => false])

<td {{ $attributes->class(['py-3', 'px-4' => ! $compact, 'px-3' => $compact, 'text-right' => $right]) }}>{{ $slot }}</td>
