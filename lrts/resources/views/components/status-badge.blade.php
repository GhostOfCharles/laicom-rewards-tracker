@props(['status'])
@php
    $labels = ['pending' => 'Processing', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'unclaimed' => 'Available', 'claim_requested' => 'Claim requested', 'released' => 'Awaiting confirmation', 'claimed' => 'Received', 'voided' => 'Voided', 'expired' => 'Expired', 'open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
    $color = in_array($status, ['approved','claimed','resolved']) ? 'success' : (in_array($status, ['rejected','voided','expired']) ? 'danger' : (in_array($status, ['cancelled','closed']) ? 'secondary' : 'warning text-dark'));
@endphp
<span {{ $attributes->class(['badge', 'bg-'.$color]) }}>{{ $labels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</span>
