<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="JobBoard API",
 *     version="1.0.0",
 *     description="Laravel 13 Job Board REST API — MySQL + Redis Pub/Sub + Elasticsearch"
 * )
 *
 * @OA\Server(url="http://localhost:8000/api/v1", description="Local")
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="role", type="string", enum={"candidate","company","admin"}),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Company",
 *
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="industry", type="string"),
 *     @OA\Property(property="city", type="string", nullable=true),
 *     @OA\Property(property="country", type="string", nullable=true),
 *     @OA\Property(property="website", type="string", nullable=true),
 *     @OA\Property(property="is_verified", type="boolean"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Job",
 *
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="category", type="string"),
 *     @OA\Property(property="employment_type", type="string", enum={"full_time","part_time","contract","internship"}),
 *     @OA\Property(property="location_city", type="string", nullable=true),
 *     @OA\Property(property="location_country", type="string"),
 *     @OA\Property(property="is_remote", type="boolean"),
 *     @OA\Property(property="salary_min", type="integer", nullable=true),
 *     @OA\Property(property="salary_max", type="integer", nullable=true),
 *     @OA\Property(property="currency", type="string"),
 *     @OA\Property(property="status", type="string", enum={"draft","active","closed"}),
 *     @OA\Property(property="published_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="company", type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="industry", type="string")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Application",
 *
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="status", type="string", enum={"pending","reviewed","shortlisted","rejected"}),
 *     @OA\Property(property="cover_letter", type="string", nullable=true),
 *     @OA\Property(property="applied_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ProblemDetails",
 *
 *     @OA\Property(property="type", type="string", example="validation_error"),
 *     @OA\Property(property="title", type="string", example="Unprocessable Entity"),
 *     @OA\Property(property="status", type="integer", example=422),
 *     @OA\Property(property="instance", type="string", example="auth/register")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *
 *     @OA\Property(property="current_page", type="integer"),
 *     @OA\Property(property="per_page", type="integer"),
 *     @OA\Property(property="total", type="integer"),
 *     @OA\Property(property="last_page", type="integer")
 * )
 */
abstract class Controller
{
    //
}
