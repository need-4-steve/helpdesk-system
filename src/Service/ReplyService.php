<?php

namespace App\Service;

use App\Entity\Reply;
use App\Entity\Ticket;
use App\Repository\ReplyRepository;
use App\Repository\TicketRepository;
use App\Utils\ErrorFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ReplyService
{
    private ReplyRepository $replyRepository;
    private ValidatorInterface $validator;
    private ErrorFormatter $errorFormatter;
    private TicketRepository $ticketRepository;

    public function __construct(
        ReplyRepository $replyRepository,
        ValidatorInterface $validator,
        ErrorFormatter $errorFormatter,
        TicketRepository $ticketRepository
    )
    {
        $this->replyRepository = $replyRepository;
        $this->validator = $validator;
        $this->errorFormatter = $errorFormatter;
        $this->ticketRepository = $ticketRepository;
    }

    public function add(Request $request, $author, Ticket $ticket)
    {
        $replyData = json_decode($request->getContent(), true);
        $message = $replyData['message'];

        $reply = new Reply();
        $reply->setMessage($message);
        $reply->setAuthor($author);
        $reply->setTicket($ticket);

        $errors = $this->validator->validate($reply);
        if ($errors->count() > 0) {
            return $this->errorFormatter->formatError($errors);
        }

        if (in_array('ROLE_ADMIN', $author->getRoles())) {
            $ticket->setStatus(Ticket::SUPPORT_REPLY);
        } else {
            $ticket->setStatus(Ticket::CUSTOMER_REPLY);
        }

        $this->ticketRepository->update();
        $this->replyRepository->save($reply);
        return $reply;
    }

    public function update(Request $request, Reply $reply): Reply
    {
        $replyData = json_decode($request->getContent(), true);
        $message = $replyData['message'];

        if (!$message) {
            throw new BadRequestHttpException('Message cannot be empty!');
        }

        $reply->setMessage($message);
        $this->replyRepository->update();

        return $reply;
    }

    public function remove(Reply $reply): bool
    {
        $this->replyRepository->remove($reply);
        return true;
    }
}