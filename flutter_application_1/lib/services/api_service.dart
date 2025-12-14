import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart';
import 'dart:convert';
import '../models/materia.dart';
import '../models/tema.dart'; 
import '../models/subtema.dart';
import '../models/contenido.dart'; 
import '../models/ejercicio.dart';

// Clase para mantener el resultado del login
class LoginResult {
  final bool success;
  final String? token;
  final String? errorMessage;

  LoginResult({
    required this.success,
    this.token,
    this.errorMessage,
  });
}

class ApiService {
  final String baseUrl = 'https://apidsw-production-9b94.up.railway.app/api'; 
  late Dio _dio;

  ApiService() {
    _dio = Dio(
      BaseOptions(
        baseUrl: 'https://apidsw-production-9b94.up.railway.app/api',
        contentType: 'application/json',
        responseType: ResponseType.plain,
        connectTimeout: const Duration(seconds: 15),
        receiveTimeout: const Duration(seconds: 15),
        headers: {
          'Accept': 'application/json',
        },
      ),
    );

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          debugPrint('🚀 REQUEST: [${options.method}] ${options.baseUrl}${options.path}');
          debugPrint('   URL Completa: ${options.uri}');
          return handler.next(options);
        },
        onResponse: (response, handler) {
          debugPrint('✅ RESPONSE: [${response.statusCode}] ${response.requestOptions.path}');
          return handler.next(response);
        },
        onError: (error, handler) {
          debugPrint('❌ ERROR INTERCEPTOR: ${error.type} - ${error.message}');
          return handler.next(error);
        },
      ),
    );
  }

  Future<LoginResult> login(String email, String password) async {
    try {
      debugPrint('🔐 Intentando login con: $email');
      debugPrint('🌐 Servidor: $baseUrl');
      debugPrint('📧 Email a enviar: "$email"');
      debugPrint('🔑 Contraseña a enviar: "$password"');
      
      final response = await _dio.post(
        '/login',
        data: {
          'email': email,
          'password': password,
        },
        options: Options(
          responseType: ResponseType.plain,
        ),
      ).timeout(Duration(seconds: 10));

      debugPrint('📊 Status Code: ${response.statusCode}');
      debugPrint('📨 Response Raw (COMPLETA): ${response.data}');
      debugPrint('📨 Response Type: ${response.data.runtimeType}');

      if (response.statusCode == 200) {
        // Limpiar caracteres basura - buscar el primer { y último }
        String rawData = response.data.toString();
        int startIndex = rawData.indexOf('{');
        int endIndex = rawData.lastIndexOf('}');
        
        if (startIndex != -1 && endIndex != -1) {
          String cleanedData = rawData.substring(startIndex, endIndex + 1);
          debugPrint('✅ Response limpia: $cleanedData');
          
          final Map<String, dynamic> jsonData = jsonDecode(cleanedData);
          // El backend devuelve 'access_token', no 'token'
          final token = jsonData['access_token'] ?? jsonData['token'];
          
          if (token == null) {
            debugPrint('❌ No se encontró token en la respuesta');
            return LoginResult(success: false, errorMessage: 'Error: No se recibió token del servidor');
          }
          
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('auth_token', token);
          
          debugPrint('✅ Login exitoso. Token guardado: $token');
          return LoginResult(success: true, token: token);
        } else {
          debugPrint('❌ No se encontró JSON válido en la respuesta');
          return LoginResult(success: false, errorMessage: 'Error: Respuesta inválida del servidor');
        }
      } else {
        debugPrint('❌ Login fallido - Status: ${response.statusCode}');
        debugPrint('❌ Respuesta RAW del servidor: ${response.data}');
        
        // Intentar extraer el mensaje de error del servidor
        String errorMessage = 'Credenciales incorrectas (Status: ${response.statusCode})';
        try {
          String rawData = response.data.toString();
          debugPrint('🔍 Intentando parsear respuesta de error: $rawData');
          
          if (rawData.contains('{')) {
            int startIndex = rawData.indexOf('{');
            int endIndex = rawData.lastIndexOf('}');
            if (startIndex != -1 && endIndex != -1) {
              String cleanedData = rawData.substring(startIndex, endIndex + 1);
              debugPrint('✂️ Respuesta limpiada: $cleanedData');
              
              final Map<String, dynamic> errorData = jsonDecode(cleanedData);
              debugPrint('📋 Mapa de error parseado: $errorData');
              
              // Intentar extraer diferentes posibles mensajes de error
              if (errorData.containsKey('message')) {
                errorMessage = errorData['message'];
              } else if (errorData.containsKey('error')) {
                errorMessage = errorData['error'];
              } else if (errorData.containsKey('errors')) {
                // Si es un objeto de errores de validación
                final errors = errorData['errors'];
                if (errors is Map) {
                  errorMessage = errors.values.first.toString();
                }
              }
              debugPrint('💬 Mensaje de error extraído: $errorMessage');
            }
          }
        } catch (e) {
          debugPrint('❌ No se pudo parsear el error del servidor: $e');
        }
        
        return LoginResult(success: false, errorMessage: errorMessage);
      }
      
    } on DioException catch (e) {
      debugPrint('🛑 DioException en login:');
      debugPrint('👉 Type: ${e.type}');
      debugPrint('👉 Message: ${e.message}');
      debugPrint('👉 Error: ${e.error}');
      
      String errorMessage = 'Error de conexión';
      
      // Detallar el tipo de error
      if (e.type == DioExceptionType.connectionTimeout) {
        errorMessage = 'Tiempo de conexión agotado. Verifica que el servidor esté disponible';
        debugPrint('⏱️ Error de timeout de conexión');
      } else if (e.type == DioExceptionType.receiveTimeout) {
        errorMessage = 'El servidor tardó demasiado en responder';
        debugPrint('⏱️ Error de timeout de respuesta');
      } else if (e.type == DioExceptionType.connectionError) {
        errorMessage = 'No se puede conectar al servidor. Verifica tu conexión de internet';
        debugPrint('📡 Error de conexión de red');
      } else if (e.type == DioExceptionType.unknown) {
        errorMessage = 'Error desconocido: ${e.error}';
        debugPrint('❓ Error desconocido');
      }
      
      if (e.response != null) {
        debugPrint('👉 Status Code: ${e.response?.statusCode}');
        debugPrint('🔥 RESPUESTA DEL SERVIDOR: ${e.response?.data}');
        
        // Intentar extraer mensaje de error
        try {
          String rawData = e.response?.data.toString() ?? '';
          if (rawData.contains('{')) {
            int startIndex = rawData.indexOf('{');
            int endIndex = rawData.lastIndexOf('}');
            if (startIndex != -1 && endIndex != -1) {
              String cleanedData = rawData.substring(startIndex, endIndex + 1);
              final Map<String, dynamic> errorData = jsonDecode(cleanedData);
              
              if (errorData.containsKey('message')) {
                errorMessage = errorData['message'];
              } else if (errorData.containsKey('error')) {
                errorMessage = errorData['error'];
              }
            }
          }
        } catch (_) {}
      }
      
      return LoginResult(success: false, errorMessage: errorMessage);
    } catch (e) {
      debugPrint('❌ Error inesperado en login: $e');
      return LoginResult(success: false, errorMessage: 'Error inesperado: $e');
    }
  }

  Future<List<Materia>> getMaterias() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      final response = await _dio.get(
        '/materias',
        options: Options(
          headers: {
            'Authorization': 'Bearer $token',
          },
        ),
      );
      
      // Limpiar caracteres basura - buscar el primer [ y último ]
      String rawData = response.data.toString();
      int startIndex = rawData.indexOf('[');
      int endIndex = rawData.lastIndexOf(']');
      
      if (startIndex != -1 && endIndex != -1) {
        String cleanedData = rawData.substring(startIndex, endIndex + 1);
        debugPrint('Respuesta materias limpia: $cleanedData');
        
        List<dynamic> data = jsonDecode(cleanedData);
        return data.map((json) => Materia.fromJson(json)).toList();
      } else {
        debugPrint('No se encontró JSON válido en la respuesta de materias');
        return [];
      }
      
    } catch (e) {
      debugPrint('Error cargando materias: $e');
      throw Exception('Error al cargar materias');
    }
  }

 Future<List<Tema>> getTemasPorMateria(int materiaId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      final response = await _dio.get(
        '$baseUrl/materias/$materiaId/temas',
        options: Options(headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        }),
      );

      // --- CORRECCIÓN DE ROBUSTEZ ---
      dynamic datos = response.data;
      
      // Si por alguna razón Dio lo leyó como String, lo convertimos nosotros
      if (datos is String) {
        debugPrint('⚠️ Recibimos String, decodificando manualmente...');
        datos = jsonDecode(datos);
      }
      
      // Ahora sí, convertimos la lista
      List<dynamic> listaLimpia = datos; 
      return listaLimpia.map((json) => Tema.fromJson(json)).toList();
      // -------------------------------
      
    } catch (e) {
      debugPrint('Error cargando temas: $e');
      throw Exception('Error al cargar temas');
    }
  }
  Future<List<Subtema>> getSubtemasPorTema(int temaId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      final response = await _dio.get(
        '$baseUrl/temas/$temaId/subtemas',
        options: Options(headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        }),
      );

      dynamic datos = response.data;
      if (datos is String) datos = jsonDecode(datos); // Por seguridad

      List<dynamic> lista = datos;
      return lista.map((json) => Subtema.fromJson(json)).toList();
    } catch (e) {
      debugPrint('Error Subtemas: $e');
      throw Exception('Error al cargar subtemas');
    }
  }
  Future<List<Contenido>> getContenidosPorSubtema(int subtemaId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      final response = await _dio.get(
        '$baseUrl/subtemas/$subtemaId/contenidos',
        options: Options(headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        }),
      );

      dynamic datos = response.data;
      if (datos is String) datos = jsonDecode(datos);

      List<dynamic> lista = datos;
      return lista.map((json) => Contenido.fromJson(json)).toList();
    } catch (e) {
      debugPrint('Error Contenidos: $e');
      throw Exception('Error al cargar contenidos');
    }
  }

  // 1. Obtener lista de ejercicios
  Future<List<Ejercicio>> getEjerciciosPorSubtema(int subtemaId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      final response = await _dio.get(
        '$baseUrl/subtemas/$subtemaId/ejercicios',
        options: Options(headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        }),
      );

      dynamic datos = response.data;
      if (datos is String) datos = jsonDecode(datos);

      List<dynamic> lista = datos;
      return lista.map((json) => Ejercicio.fromJson(json)).toList();
    } catch (e) {
      debugPrint('Error Ejercicios: $e');
      throw Exception('Error al cargar ejercicios');
    }
  }

  // 2. Enviar respuesta a la IA para evaluación
  Future<Map<String, dynamic>> evaluarRespuestaIA(int ejercicioId, String respuestaUsuario) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      debugPrint('Enviando a IA: ID=$ejercicioId, Resp=$respuestaUsuario'); // <--- DEBUG 1

      final response = await _dio.post(
        '$baseUrl/ia/verificar-ejercicio', 
        data: {
          'ejercicio_id': ejercicioId,
          'respuesta_estudiante': respuestaUsuario,
        },
        options: Options(headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        }),
      );

      debugPrint('Respuesta IA Exitosa: ${response.data}'); // <--- DEBUG 2
      return response.data; 

    } catch (e) {
      debugPrint('🛑 ERROR EN EVALUAR RESPUESTA IA: $e');

      // SI EL SERVIDOR RESPONDIÓ CON ERROR (Ej: 500, 404, 422)
      if (e is DioException) {
        if (e.response != null) {
          debugPrint('Código de estado: ${e.response?.statusCode}');
          debugPrint('Datos del error: ${e.response?.data}'); // <--- AQUÍ SALDRÁ EL ERROR DE LARAVEL
          
          // Intentamos devolver el mensaje real del servidor si existe
          return {
            'error': true,
            'retroalimentacion': 'Error del servidor (${e.response?.statusCode}): ${e.response?.data}'
          };
        } else {
          // Error de conexión (servidor apagado, internet, etc.)
          debugPrint('Error de conexión: ${e.message}');
          return {
            'error': true,
            'retroalimentacion': 'Error de conexión: Verifique que el servidor corre en 10.0.2.2:8000'
          };
        }
      }

      // Error desconocido
      return {
        'error': true,
        'retroalimentacion': 'Error desconocido en la App: $e'
      };
    }
  }
  // ---------------------------------------------------------
  // NUEVAS FUNCIONES PARA QUIZZES (Cuestionarios)
  // ---------------------------------------------------------

  /// 1. Generar un nuevo Quiz basado en un tema
  Future<Map<String, dynamic>> generarCuestionario({
    int? temaId, 
    int? subtemaId, 
    int cantidad = 5, 
    String dificultad = 'intermedio'
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      Map<String, dynamic> requestData = {
        'cantidad': cantidad,
        'dificultad': dificultad,
      };

      if (subtemaId != null) requestData['subtema_id'] = subtemaId;
      if (temaId != null) requestData['tema_id'] = temaId;

      debugPrint('🚀 ENVIANDO A LARAVEL: $requestData');

      final response = await _dio.post(
        '$baseUrl/ia/generar-cuestionario',
        data: requestData,
        options: Options(
          headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'application/json'
          },
          // Esto es vital: le decimos a Dio que acepte texto plano también
          responseType: ResponseType.plain, 
        ),
      );
      
      debugPrint('📥 RESPUESTA RECIBIDA (RAW): ${response.data}');

      // --- AQUÍ ESTÁ LA MAGIA QUE ARREGLA EL ERROR ---
      dynamic datos = response.data;

      // Si llegó como String, lo convertimos a Mapa manualmente
      if (datos is String) {
        // Intentamos limpiar posibles caracteres basura antes del JSON
        if (datos.contains('{')) {
            datos = datos.substring(datos.indexOf('{'));
        }
        datos = jsonDecode(datos);
      }

      return Map<String, dynamic>.from(datos);
      // ----------------------------------------------

    } catch (e) {
      debugPrint('🛑 ERROR GRAVE AL GENERAR CUESTIONARIO: $e');
      throw Exception('No se pudo generar el cuestionario.');
    }
  }

  /// 2. Enviar las respuestas del usuario para calificar
 // 2. Responder Quiz (Versión Blindada contra Strings)
  Future<Map<String, dynamic>> responderCuestionario(int quizId, List<Map<String, dynamic>> respuestas) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      final response = await _dio.post(
        '$baseUrl/ia/responder-cuestionario',
        data: {
          'quiz_id': quizId,
          'respuestas': respuestas,
        },
        options: Options(
          headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'application/json'
          },
          // TRUCO VITAL: Pedimos texto plano para decodificar nosotros mismos
          responseType: ResponseType.plain, 
        ),
      );

      debugPrint('📥 RESPUESTA CALIFICACIÓN (RAW): ${response.data}');

      // --- LOGICA DE DECODIFICACIÓN MANUAL ---
      dynamic datos = response.data;

      if (datos is String) {
        // Limpieza de emergencia por si Laravel manda basura antes del JSON
        if (datos.contains('{')) {
            datos = datos.substring(datos.indexOf('{'));
        }
        datos = jsonDecode(datos);
      }

      return Map<String, dynamic>.from(datos);
      // ---------------------------------------

    } catch (e) {
      debugPrint('🛑 ERROR AL CALIFICAR CUESTIONARIO: $e');
      throw Exception('No se pudo calificar el cuestionario');
    }
    
  }
  Future<Map<String, dynamic>> evaluarQuiz(List<Map<String, dynamic>> listaRespuestas) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      // 1. Enviamos la lista de respuestas: [{'ejercicio_id': 1, 'respuesta': '...'}, ...]
      final response = await _dio.post(
        '$baseUrl/ia/evaluar-quiz', // Asegúrate de que esta ruta exista en Laravel (routes/api.php)
        data: {
          'respuestas': listaRespuestas,
        },
        options: Options(
          headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'application/json'
          },
          // IMPORTANTE: Forzamos respuesta plana para evitar errores de conversión
          responseType: ResponseType.plain, 
        ),
      );

      // 2. Decodificación manual segura (Misma lógica que usamos antes)
      dynamic datos = response.data;

      if (datos is String) {
        if (datos.contains('{')) {
            datos = datos.substring(datos.indexOf('{'));
        }
        datos = jsonDecode(datos);
      }

      // 3. El backend devuelve algo como: { "message": "...", "resultado": { ... } }
      // Devolvemos la parte de "resultado" o todo el mapa según tu backend.
      // Si tu backend devuelve directamente el resultado dentro de 'resultado', usa:
      if (datos is Map<String, dynamic> && datos.containsKey('resultado')) {
        return Map<String, dynamic>.from(datos['resultado']);
      }
      
      return Map<String, dynamic>.from(datos);

    } catch (e) {
      // --- BLOQUE DE DEPURACIÓN MEJORADO ---
      debugPrint('🛑 ERROR CRÍTICO EN EVALUAR QUIZ:');
      
      if (e is DioException) {
        // Error relacionado con la petición HTTP
        debugPrint('👉 TIPO: DioException');
        debugPrint('👉 STATUS CODE: ${e.response?.statusCode}');
        debugPrint('👉 MENSAJE DIO: ${e.message}');
        
        if (e.response != null) {
          debugPrint('🔥🔥 DATA DEL SERVIDOR (LARAVEL) 🔥🔥:');
          // Aquí saldrá el mensaje exacto de Laravel (ej: "column not found", "validate error")
          debugPrint(e.response?.data.toString()); 
        } else {
          debugPrint('👉 Error de conexión: El servidor no respondió.');
        }

      } else {
        // Error de lógica en Flutter (ej: falló el jsonDecode)
        debugPrint('👉 TIPO: Error Interno / Parsing');
        debugPrint('👉 DETALLE: $e');
        
        // Si es error de tipo (String is not subtype...), imprime el stack trace
        if (e is TypeError) {
           debugPrint('👉 STACK TRACE: ${e.stackTrace}');
        }
      }
      
      debugPrint('--------------------------------------------------');
      
      // Lanzamos un error más descriptivo para que la UI sepa qué decir
      throw Exception('No se pudo evaluar: ${e.toString()}');
    }
  }

}