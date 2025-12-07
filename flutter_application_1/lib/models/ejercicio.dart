class Ejercicio {
  final int id;
  final int subtemaId;
  final String titulo;
  final String pregunta;
  final String? dificultad;

  Ejercicio({
    required this.id,
    required this.subtemaId,
    required this.titulo,
    required this.pregunta,
    this.dificultad,
  });

  factory Ejercicio.fromJson(Map<String, dynamic> json) {
    return Ejercicio(
      id: json['id'],
      subtemaId: int.parse(json['subtema_id'].toString()),
      titulo: json['titulo'],
      pregunta: json['pregunta'],
      dificultad: json['dificultad'],
    );
  }
}