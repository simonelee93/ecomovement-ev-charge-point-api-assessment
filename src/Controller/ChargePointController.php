<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\ChargePointInputParser;
use App\Http\ChargePointPresenter;
use App\Http\Request;
use App\Http\Response;
use App\Repository\ChargePointRepository;
use InvalidArgumentException;

class ChargePointController
{
    public function __construct(
        private readonly ChargePointRepository $repository,
        private readonly ChargePointInputParser $inputParser = new ChargePointInputParser(),
        private readonly ChargePointPresenter $presenter = new ChargePointPresenter(),
    ) {
    }

    public function create(Request $request): Response
    {
        try {
            $newChargePoint = $this->inputParser->parseCreate($request->getBody());
        } catch (InvalidArgumentException $exception) {
            return new Response(['error' => $exception->getMessage()], 400);
        }

        $chargePoint = $this->repository->save($newChargePoint);

        return new Response($this->presenter->toArray($chargePoint), 201);
    }

    public function show(Request $request, array $params): Response
    {
        try {
            $id = $this->inputParser->parseId($params['id'] ?? null);
        } catch (InvalidArgumentException $exception) {
            return new Response(['error' => $exception->getMessage()], 400);
        }

        $chargePoint = $this->repository->findById($id);

        if ($chargePoint === null) {
            return new Response(['error' => 'Charge point not found'], 404);
        }

        return new Response($this->presenter->toArray($chargePoint));
    }

    public function nearby(Request $request): Response
    {
        try {
            $search = $this->inputParser->parseNearby($request->getQueryParameters());
        } catch (InvalidArgumentException $exception) {
            return new Response(['error' => $exception->getMessage()], 400);
        }

        $chargePoints = $this->repository->findNearby($search->latitude, $search->longitude, $search->radius);

        return new Response(array_map($this->presenter->toArray(...), $chargePoints));
    }
}
