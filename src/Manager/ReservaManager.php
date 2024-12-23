<?php

namespace App\Manager;

use App\Entity\Reserva;
use App\Entity\Usuario;
use App\Entity\Vehiculo;
use App\Repository\ReservaRepository;
use App\Repository\VehiculoRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReservaManager
{
    private $vehiculoRepository, $reservaRepository;
    private $entityManager;

    public function __construct(VehiculoRepository $vehiculoRepository, ReservaRepository $reservaRepository, EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->vehiculoRepository = $vehiculoRepository;
        $this->reservaRepository = $reservaRepository;
    }

    public function calcularTotalReserva(Vehiculo $vehiculo, \DateTime $fecha_inicio, \DateTime $fecha_finalizacion, int $cantidadPersonas)
    {
        $dias = $fecha_inicio->diff($fecha_finalizacion)->days;

        if ($dias > 0) {
            return $dias * $vehiculo->getValor();
        }

        return null;
    }

    public function crearReserva(Usuario $usuario, Vehiculo $vehiculo, \DateTime $fecha_inicio, \DateTime $fecha_finalizacion, int $cantidadPersonas): Reserva
    {
        $reserva = new Reserva();
        $reserva->setVehiculo($vehiculo);
        $reserva->setFechaInicio($fecha_inicio);
        $reserva->setFechaFinalizacion($fecha_finalizacion);
        $reserva->setCantidadPersonas($cantidadPersonas);
        $reserva->setUsuario($usuario);

        $total = $vehiculo->getValor() * ($fecha_finalizacion->diff($fecha_inicio)->days);
        $reserva->setTotal($total);

        $this->entityManager->persist($reserva);
        $this->entityManager->flush();
        return $reserva;
    }

    public function obtenerVehiculoPorId(int $id)
    {
        return $this->vehiculoRepository->find($id);
    }

    public function getReservas(Usuario $usuario)
    {
        return $this->reservaRepository->findBy(['usuario' => $usuario]);
    }

    public function obtenerTodasLasOrdenes()
    {
        return $this->reservaRepository->findAll();
    }

    public function obtenerOrdenesFiltradas(?\DateTime $fecha_inicio, ?\DateTime $fecha_finalizacion)
    {
        $queryBuilder = $this->reservaRepository->createQueryBuilder('r');

        if ($fecha_inicio) {
            $queryBuilder->andWhere('r.fecha_inicio >= :fecha_inicio')
                         ->setParameter('fecha_inicio', $fecha_inicio);
        }

        if ($fecha_finalizacion) {
            $queryBuilder->andWhere('r.fecha_finalizacion <= :fecha_finalizacion')
                         ->setParameter('fecha_finalizacion', $fecha_finalizacion);
        }

        return $queryBuilder->orderBy('r.fecha_inicio', 'DESC')
                            ->getQuery()
                            ->getResult();
    }

    public function verificarDisponibilidad(Vehiculo $vehiculo, \DateTime $fecha_inicio, \DateTime $fecha_finalizacion): bool
    {
        $reservas = $this->reservaRepository->findBy(['vehiculo' => $vehiculo]);

        foreach ($reservas as $reserva) {
            if (
                ($fecha_inicio <= $reserva->getFechaFinalizacion() && $fecha_finalizacion >= $reserva->getFechaInicio())
            ) {
                return false;
            }
        }

        return true;
    }
}
