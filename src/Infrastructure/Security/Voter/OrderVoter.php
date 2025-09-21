<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Voter;

use App\Domain\Entity\Order;
use App\Domain\Entity\User;
use App\Domain\Security\UserRoles;
use App\Domain\ValueObject\Email;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

use function array_key_exists;
use function in_array;
use function is_array;
use function strcasecmp;

/**
 * @extends Voter<string, Order>
 * @psalm-extends Voter<string, Order>
 */
final class OrderVoter extends Voter
{
    public const string VIEW = 'ORDER_VIEW';
    public const string CONVERT = 'ORDER_CONVERT';
    public const string UPDATE_STATUS = 'ORDER_UPDATE_STATUS';

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, [self::VIEW, self::CONVERT, self::UPDATE_STATUS], true);
    }

    /**
     * Accept anything here to stay contravariant with parent Voter::supports().
     *
     * @psalm-assert-if-true Order|array{order: Order, guest_email?: string} $subject
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$this->supportsAttribute($attribute)) {
            return false;
        }

        if ($subject instanceof Order) {
            return true;
        }

        return is_array($subject)
            && isset($subject['order'])
            && $subject['order'] instanceof Order;
    }

    public function supportsType(string $subjectType): bool
    {
        return $subjectType === 'array' || $subjectType === Order::class;
    }

    /**
     * @param array{order: Order, guest_email?: string}|Order $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $order = $this->extractOrder($subject);
        $guestEmail = $this->extractGuestEmail($subject);

        $user = $token->getUser();
        $authenticatedUser = $user instanceof User ? $user : null;

        $isAdmin = $authenticatedUser instanceof User && $authenticatedUser->isAdmin();

        return match ($attribute) {
            self::VIEW => $this->canView($order, $authenticatedUser, $guestEmail, $isAdmin),
            self::CONVERT => $this->canConvert($order, $authenticatedUser, $isAdmin),
            self::UPDATE_STATUS => $isAdmin,
            default => false,
        };
    }

    private function canView(Order $order, ?User $user, ?string $guestEmail, bool $isAdmin): bool
    {
        if ($isAdmin) {
            return true;
        }

        if ($order->isUserOrder()) {
            return $this->isOwnedByUser($order, $user);
        }

        return $this->guestEmailMatches($order, $guestEmail);
    }

    private function canConvert(Order $order, ?User $user, bool $isAdmin): bool
    {
        if ($isAdmin) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($order->isUserOrder()) {
            return $this->isOwnedByUser($order, $user);
        }

        return in_array(UserRoles::CUSTOMER, $user->getRoles(), true);
    }

    private function isOwnedByUser(Order $order, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $customer = $order->getCustomer();
        if (!$customer instanceof User) {
            return false;
        }

        return $customer->getId() === $user->getId();
    }

    private function guestEmailMatches(Order $order, ?string $candidate): bool
    {
        if ($candidate === null || $candidate === '') {
            return false;
        }

        $guestEmail = $order->getGuestEmail();
        if ($guestEmail === null) {
            return false;
        }

        try {
            $normalized = new Email($candidate);
        } catch (InvalidArgumentException) {
            return false;
        }

        return strcasecmp($guestEmail->getValue(), $normalized->getValue()) === 0;
    }

    /**
     * @param array{order: Order, guest_email?: string}|Order $subject
     */
    private function extractOrder(mixed $subject): Order
    {
        if ($subject instanceof Order) {
            return $subject;
        }

        return $subject['order'];
    }

    /**
     * @param array{order: Order, guest_email?: string}|Order $subject
     */
    private function extractGuestEmail(mixed $subject): ?string
    {
        if (is_array($subject) && array_key_exists('guest_email', $subject)) {
            return $subject['guest_email'];
        }

        return null;
    }
}
