import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart';
import 'dart:convert';
import '../models/materia.dart';
import '../models/tema.dart'; 
import '../models/subtema.dart';
import '../models/contenido.dart'; 
import '../models/ejercicio.dart';

class ApiService {
  final String baseUrl = 'http://10.0.2.2:8000/api'; 
  late Dio _dio;

  ApiService() {
    _dio = Dio(
      BaseOptions(
        baseUrl: 'http://10.0.2.2:8000/api',
        contentType: 'application/json',
        responseType: ResponseType.plain, // USAR PLAIN PARA EVITAR JSON PARSING
        headers: {
          'Accept': 'application/json',
        },
      ),
    );

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          debugPrint('>>> [${options.method}] ${options.path}');
          return handler.next(options);
        },
        onResponse: (response, handler) {
          debugPrint('<<< [${response.statusCode}] ${response.requestOptions.path}');
          return handler.next(response);
        },
        onError: (error, handler) {
          debugPrint('!!! ERROR: ${error.type} - ${error.message}');
          return handler.next(error);
        },
      ),
    );
  }

  Future<bool> login(String email, String password) async {
    try {
      debugPrint('Intentando login con: $email');
      
      final response = await _dio.post(
        '/login',
        data: {
          'email': email,
          'password': password,
        },
      ).timeout(Duration(seconds: 10));

      debugPrint('Status Code: ${response.statusCode}');

      if (response.statusCode == 200) {
        // Limpiar caracteres basura - buscar el primer { y último }
        String rawData = response.data.toString();
        int startIndex = rawData.indexOf('{');
        int endIndex = rawData.lastIndexOf('}');
        
        if (startIndex != -1 && endIndex != -1) {
          String cleanedData = rawData.substring(startIndex, endIndex + 1);
          debugPrint('Response limpia: $cleanedData');
          
          final Map<String, dynamic> jsonData = jsonDecode(cleanedData);
          final token = jsonData['token'];
          
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('auth_token', token);
          
          debugPrint('Login exitoso. Token guardado: $token');
          return true;
        } else {
          debugPrint('No se encontró JSON válido en la respuesta');
          return false;
        }
      }
      
      return false;
      
    } on DioException catch (e) {
      debugPrint('DioException: ${e.type}');
      debugPrint('Message: ${e.message}');
      return false;
    } catch (e) {
      debugPrint('Error inesperado login: $e');
      return false;
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

      // Preparamos los datos (enviaremos temaId o subtemaId según corresponda)
      Map<String, dynamic> data = {
        'cantidad': cantidad,
        'dificultad': dificultad,
      };

      if (subtemaId != null) data['subtema_id'] = subtemaId;
      if (temaId != null) data['tema_id'] = temaId;

      final response = await _dio.post(
        '$baseUrl/ia/generar-cuestionario',
        data: data,
        options: Options(headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'}),
      );
      
      return response.data;
    } catch (e) {
      debugPrint('Error generando quiz: $e');
      throw Exception('No se pudo generar el cuestionario');
    }
  }

  /// 2. Enviar las respuestas del usuario para calificar
  Future<Map<String, dynamic>> responderCuestionario(int quizId, List<Map<String, dynamic>> respuestas) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      // Las respuestas deben tener este formato: [{'id': 1, 'seleccion': 'a'}, {'id': 2, 'seleccion': 'c'}...]
      final response = await _dio.post(
        '$baseUrl/ia/responder-cuestionario',
        data: {
          'quiz_id': quizId,
          'respuestas': respuestas,
        },
        options: Options(headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        }),
      );

      // Laravel devuelve 'calificacion', 'retroalimentacion', etc.
      return response.data;

    } catch (e) {
      debugPrint('Error respondiendo cuestionario: $e');
      if (e is DioException) {
        debugPrint('Server error: ${e.response?.data}');
      }
      throw Exception('Error al calificar el cuestionario');
    }
  }
}