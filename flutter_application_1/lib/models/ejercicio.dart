import 'dart:convert';

class Ejercicio {
  final int id;
  final int subtemaId; 
  final String titulo;
  final String pregunta;
  final String? dificultad;
  final String? solucion;
  
  // NUEVOS CAMPOS
  final String tipoInteraccion; 
  final Map<String, dynamic>? contenidoJuego;

  Ejercicio({
    required this.id,
    required this.subtemaId,
    required this.titulo,
    required this.pregunta,
    this.dificultad,
    this.solucion,
    required this.tipoInteraccion,
    this.contenidoJuego,
  });

  factory Ejercicio.fromJson(Map<String, dynamic> json) {
    return Ejercicio(
      id: json['id'],
      subtemaId: int.parse(json['subtema_id'].toString()),
      titulo: json['titulo'] ?? 'Ejercicio',
      pregunta: json['pregunta'],
      dificultad: json['dificultad'],
      solucion: json['solucion'],
      // Mapeo de los nuevos campos con valores por defecto
      tipoInteraccion: json['tipo_interaccion'] ?? 'texto_libre',
      contenidoJuego: json['contenido_juego'] is String 
          ? jsonDecode(json['contenido_juego']) 
          : json['contenido_juego'],
    );
  }
}